<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Services\RequestContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * 在线授权客户端编排。
 *
 * 在线授权产出的仍是现有格式的离线 license blob，只是「获取方式」变成用 appstore 账号登录
 * 自助签发、并由本类定时续期。doo.so 本地校验、license 文件存储全部复用。绑定状态以单例
 * 形式存于 settings 表（name=onlineLicense），instance_token 用 Crypt 加密。
 *
 * 四级状态机（基于租约内嵌到期 lease_expired_at 与本地宽限，全程不依赖 appstore 可达）：
 *   active   续期正常
 *   reminder 续期失败/租约剩余不足 warn_days（仅管理员可见提醒）
 *   frozen   租约已过期（doo.so 既有行为：限制新增用户）
 *   revoked  冻结超过 grace_days 或 appstore 明确吊销 → 回落默认 3 人版
 */
class OnlineLicense
{
    const KEY = 'onlineLicense';

    // ---- 配置 ----

    protected static function appstoreUrl(): string
    {
        return rtrim((string)config('dootask.online_license_appstore_url'), '/');
    }

    protected static function renewWithinDays(): int
    {
        return (int)config('dootask.online_license_renew_within_days', 20);
    }

    protected static function warnDays(): int
    {
        return (int)config('dootask.online_license_warn_days', 7);
    }

    protected static function graceDays(): int
    {
        return (int)config('dootask.online_license_grace_days', 14);
    }

    // ---- 状态读写（单例 settings）----

    public static function get(): array
    {
        return Base::setting(self::KEY) ?: [];
    }

    protected static function set(array $patch): array
    {
        $next = array_merge(self::get(), $patch);
        Base::setting(self::KEY, $next);
        return $next;
    }

    public static function enabled(): bool
    {
        $s = self::get();
        return !empty($s['enabled']) && ($s['mode'] ?? '') === 'online';
    }

    protected static function token(): string
    {
        $enc = self::get()['instance_token'] ?? '';
        if (empty($enc)) {
            return '';
        }
        try {
            return Crypt::decryptString($enc);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 当前请求语言，透传给 appstore 用于邮件按语言渲染（中文/繁体→中文，其余→英文）。
     * 非请求上下文（如定时续期）返回空串，由 appstore 回落默认语言。
     */
    protected static function lang(): string
    {
        return (string)Base::headerOrInput('language');
    }

    protected static function fingerprint(): array
    {
        return [
            'sn' => Doo::dooSN(),
            'macs' => implode(',', Doo::macs()),
            // 优先真实外网地址：config('app.url') 若为 localhost 由 replaceBaseUrl 替换为缓存的访问地址
            'url' => RequestContext::replaceBaseUrl((string)config('app.url')),
            // DooTask 应用版本（非 doo.so 库版本）
            'version' => Base::getVersion(),
        ];
    }

    // ---- appstore 调用 ----

    /**
     * 调 appstore license 接口。返回 ['ok'=>bool, 'data'=>array, 'message'=>string]。
     * $bearer 非空时带实例令牌（续期/释放）。
     */
    protected static function call(string $path, array $payload, string $bearer = ''): array
    {
        $url = self::appstoreUrl() . '/api/v1/license/' . ltrim($path, '/');
        $headers = ['Content-Type' => 'application/json'];
        if ($bearer !== '') {
            $headers['Authorization'] = 'Bearer ' . $bearer;
        }
        $resp = Ihttp::ihttp_request($url, json_encode($payload, JSON_UNESCAPED_UNICODE), $headers, 15);
        if (Base::isError($resp)) {
            return ['ok' => false, 'data' => [], 'message' => $resp['msg'] ?: '无法连接授权服务'];
        }
        $body = Base::json2array($resp['data'] ?? '');
        if (($body['code'] ?? 0) !== 200) {
            return ['ok' => false, 'data' => [], 'message' => $body['message'] ?: '授权服务返回错误'];
        }
        return ['ok' => true, 'data' => $body['data'] ?? [], 'message' => ''];
    }

    /**
     * 处理签发结果：issued/renewed 则落地 license + 更新绑定状态；其它状态原样返回供上层决策。
     */
    protected static function applyIssue(string $account, array $d): string
    {
        $status = $d['status'] ?? '';
        if (in_array($status, ['issued', 'renewed'], true)) {
            $blob = $d['license'] ?? '';
            if ($blob === '') {
                throw new ApiException('授权服务未返回 license');
            }
            Doo::licenseSave($blob); // 复用离线落地与 doo.so 校验
            $snap = $d['snapshot'] ?? [];
            $patch = [
                'enabled' => true,
                'mode' => 'online',
                'account' => $account,
                'plan' => $snap['plan'] ?? '',
                'people' => $snap['people'] ?? 0,
                'valid_until' => $snap['valid_until'] ?? null,
                'lease_expired_at' => $snap['lease_expired_at'] ?? null,
                'server_status' => $status,
                'error_count' => 0,
                'last_error' => '',
                'frozen_since' => null,
                'last_renewed_at' => Carbon::now()->toDateTimeString(),
            ];
            if (!empty($d['instance_token'])) {
                $patch['instance_token'] = Crypt::encryptString($d['instance_token']);
            }
            self::set($patch);
            self::computeStage();
        }
        return $status;
    }

    // ---- 对外动作 ----

    /**
     * 发送邮箱验证码（登录与试用共用），返回脱敏邮箱。
     */
    public static function emailSend(string $email): string
    {
        $r = self::call('email/send', ['email' => $email, 'lang' => self::lang()]);
        if (!$r['ok']) {
            throw new ApiException($r['message']);
        }
        return $r['data']['email'] ?? '';
    }

    /**
     * 邮箱 + 验证码登录并签发。失败抛 ApiException。
     * 本机有多条可用授权时，appstore 返回 select_required + candidates，
     * 此处不签发、原样返回候选，由前端选定后走 loginConfirm()。
     */
    public static function login(string $email, string $code): array
    {
        $r = self::call('login', array_merge(['email' => $email, 'code' => $code, 'lang' => self::lang()], self::fingerprint()));
        if (!$r['ok']) {
            throw new ApiException($r['message']);
        }
        $d = $r['data'];
        if (($d['status'] ?? '') === 'select_required') {
            return [
                'select_required' => true,
                'candidates' => $d['candidates'] ?? [],
            ];
        }
        $status = self::applyIssue($email, $d);
        if (!in_array($status, ['issued', 'renewed'], true)) {
            throw new ApiException(self::statusHint($status));
        }
        return self::status();
    }

    /**
     * 多条可用授权时，用户选定 $entitlementId 后确认签发（复用同一验证码）。失败抛 ApiException。
     */
    public static function loginConfirm(string $email, string $code, int $entitlementId): array
    {
        $payload = array_merge([
            'email' => $email,
            'code' => $code,
            'entitlement_id' => $entitlementId,
            'lang' => self::lang(),
        ], self::fingerprint());
        $r = self::call('login/confirm', $payload);
        if (!$r['ok']) {
            throw new ApiException($r['message']);
        }
        $status = self::applyIssue($email, $r['data']);
        if (!in_array($status, ['issued', 'renewed'], true)) {
            throw new ApiException(self::statusHint($status));
        }
        return self::status();
    }

    /**
     * 邮箱 + 验证码申请试用并签发。
     */
    public static function trial(string $email, string $code): array
    {
        $payload = array_merge(['email' => $email, 'code' => $code, 'lang' => self::lang()], self::fingerprint());
        $r = self::call('trial', $payload);
        if (!$r['ok']) {
            throw new ApiException($r['message']);
        }
        $status = self::applyIssue($email, $r['data']);
        if (!in_array($status, ['issued', 'renewed'], true)) {
            throw new ApiException(self::statusHint($status));
        }
        return self::status();
    }

    /**
     * 续期（定时任务调用）。不抛异常：网络/服务错误只累加计数，最终由状态机本地降级。
     */
    public static function renew(): void
    {
        if (!self::enabled()) {
            return;
        }
        $token = self::token();
        if ($token === '') {
            return;
        }
        $r = self::call('renew', self::fingerprint(), $token);
        if (!$r['ok']) {
            $s = self::get();
            self::set([
                'error_count' => (int)($s['error_count'] ?? 0) + 1,
                'last_error' => $r['message'],
            ]);
            self::computeStage();
            return;
        }
        $status = $r['data']['status'] ?? '';
        if (in_array($status, ['issued', 'renewed'], true)) {
            self::applyIssue(self::get()['account'] ?? '', $r['data']);
            return;
        }
        // 服务侧明确状态（revoked/suspended/no_entitlement）：不延长租约，记录后交状态机
        self::set(['server_status' => $status, 'last_error' => self::statusHint($status)]);
        self::computeStage();
    }

    /**
     * 是否到了该续期的时间（租约剩余不足 renew_within_days）。
     */
    public static function dueForRenew(): bool
    {
        $lease = self::get()['lease_expired_at'] ?? null;
        if (!$lease) {
            return true;
        }
        return Carbon::parse($lease)->lte(Carbon::now()->addDays(self::renewWithinDays()));
    }

    /**
     * 定时续期入口：由容器内独立进程的 artisan 命令（online-license:renew）按小时调用。
     * 先本地状态机推进（断网也能降级 frozen→revoked），再在租约将尽时续期。
     */
    public static function cron(): void
    {
        if (!self::enabled()) {
            return;
        }
        self::computeStage();
        if (self::enabled() && self::dueForRenew()) {
            self::renew();
        }
    }

    /**
     * 进入授权页时的静默刷新：服务可达则按服务结果更新（成功续签 / 反映吊销冻结），
     * 网络失败则什么都不做、不提示、不降级（避免一次页面刷新失败就误报）。
     */
    public static function refresh(): void
    {
        if (!self::enabled()) {
            return;
        }
        $token = self::token();
        if ($token === '') {
            return;
        }
        try {
            $r = self::call('renew', self::fingerprint(), $token);
            if (!$r['ok']) {
                return; // 刷新失败：不更新、不提示
            }
            $status = $r['data']['status'] ?? '';
            if (in_array($status, ['issued', 'renewed'], true)) {
                self::applyIssue(self::get()['account'] ?? '', $r['data']);
            } elseif (in_array($status, ['revoked', 'suspended', 'no_entitlement'], true)) {
                // 服务侧明确结果（非网络失败）：如实反映
                self::set(['server_status' => $status, 'last_error' => self::statusHint($status)]);
                self::computeStage();
            }
        } catch (\Throwable) {
            // 忽略，保持现状
        }
    }

    /**
     * 退出在线授权：释放座位 + 回落默认。
     */
    public static function logout(): void
    {
        $token = self::token();
        if ($token !== '') {
            self::call('deactivate', [], $token);
        }
        self::fallbackToDefault();
        Base::setting(self::KEY, ['enabled' => false, 'mode' => 'offline']);
    }

    /**
     * 切换到离线授权（互斥）：保存离线 license 后调用。
     * 尽力释放在线座位 + 清在线标志，但「不」删除 license 文件（刚保存的离线 license 要保留）。
     */
    public static function switchToOffline(): void
    {
        if (!self::enabled()) {
            return;
        }
        $token = self::token();
        if ($token !== '') {
            self::call('deactivate', [], $token); // 尽力释放座位，失败忽略
        }
        Base::setting(self::KEY, ['enabled' => false, 'mode' => 'offline']);
    }

    // ---- 状态机 ----

    /**
     * 据租约到期 + 宽限重新计算 status，并在 revoked 时执行降级。
     */
    public static function computeStage(): string
    {
        $s = self::get();
        if (($s['mode'] ?? '') !== 'online' || empty($s['enabled'])) {
            return 'offline';
        }
        $now = Carbon::now();
        $server = $s['server_status'] ?? '';
        $lease = $s['lease_expired_at'] ?? null;

        if ($server === 'revoked') {
            return self::transitionRevoked();
        }

        if ($lease && Carbon::parse($lease)->lte($now)) {
            // 租约已过期 → 冻结；超过宽限 → 吊销
            $frozenSince = $s['frozen_since'] ?? null;
            if (!$frozenSince) {
                $frozenSince = $now->toDateTimeString();
                self::set(['frozen_since' => $frozenSince]);
            }
            if (Carbon::parse($frozenSince)->addDays(self::graceDays())->lte($now)) {
                return self::transitionRevoked();
            }
            self::set(['status' => 'frozen']);
            return 'frozen';
        }

        // 租约有效
        $remindByLease = $lease && Carbon::parse($lease)->lte($now->copy()->addDays(self::warnDays()));
        $remindByError = (int)($s['error_count'] ?? 0) > 0 || $server === 'suspended' || $server === 'no_entitlement';
        $status = ($remindByLease || $remindByError) ? 'reminder' : 'active';
        self::set(['status' => $status, 'frozen_since' => null]);
        return $status;
    }

    protected static function transitionRevoked(): string
    {
        self::fallbackToDefault();
        self::set(['status' => 'revoked', 'enabled' => false]);
        return 'revoked';
    }

    /**
     * 删除在线 license 文件，让 dooso 回落默认 3 人版（触发既有超员禁用）。
     */
    protected static function fallbackToDefault(): void
    {
        foreach (['LICENSE', 'license'] as $name) {
            $path = config_path($name);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    // ---- 提醒文案（注入 system/license 的 error[]，复用 dashboard 警告条与 license 页）----

    public static function stageMessages(): array
    {
        if (!self::enabled() && (self::get()['status'] ?? '') !== 'revoked') {
            return [];
        }
        $s = self::get();
        $status = $s['status'] ?? self::computeStage();
        $msgs = [];
        switch ($status) {
            case 'reminder':
                if (($s['server_status'] ?? '') === 'suspended') {
                    $msgs[] = '在线授权已被冻结，请联系服务商';
                } elseif ((int)($s['error_count'] ?? 0) > 0) {
                    $msgs[] = '在线授权续期失败，请检查网络';
                } else {
                    $msgs[] = '在线授权即将到期，请保持联网续期';
                }
                break;
            case 'frozen':
                $msgs[] = '在线授权已过期，新增用户受限，请尽快续期';
                break;
            case 'revoked':
                $msgs[] = '在线授权已失效，已回落到基础版';
                break;
        }
        return $msgs;
    }

    protected static function statusHint(string $status): string
    {
        return match ($status) {
            'no_entitlement' => '该账号暂无可用授权，请先申请试用或购买',
            'revoked' => '该授权已被吊销',
            'suspended' => '该授权已被冻结',
            'seat_taken' => '该授权已在另一台实例使用，请先在原实例释放（换机）',
            'entitlement_expired' => '该授权已到期',
            default => '签发失败（' . $status . '）',
        };
    }

    /**
     * 对外状态（前端在线 Tab / status 接口用，不含敏感 token）。
     */
    public static function status(): array
    {
        $s = self::get();
        if (($s['mode'] ?? '') !== 'online' || empty($s['enabled'])) {
            return ['mode' => 'offline'];
        }
        return [
            'mode' => 'online',
            'account' => $s['account'] ?? '',
            'plan' => $s['plan'] ?? '',
            'people' => $s['people'] ?? 0,
            'valid_until' => $s['valid_until'] ?? null,
            'lease_expired_at' => $s['lease_expired_at'] ?? null,
            'last_renewed_at' => $s['last_renewed_at'] ?? null,
            'status' => $s['status'] ?? self::computeStage(),
            'error_count' => (int)($s['error_count'] ?? 0),
            'last_error' => $s['last_error'] ?? '',
        ];
    }
}
