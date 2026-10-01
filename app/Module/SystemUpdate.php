<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\WebSocket;
use App\Tasks\PushTask;
use Cache;

/**
 * 系统更新（主程序在线更新）
 *
 * 执行链路：php → appstore 内部接口（APP_KEY 鉴权）→ appstore 拉起独立的 updater 容器执行 `./cmd update --non-interactive`。
 * updater 不属于 compose 项目，更新过程中 php/nginx/appstore 被重建也不受影响；
 * 进度由 cmd 写入 docker/logs/update/state.json，这里直接读取（php 重启期间前端轮询短暂失败属正常）。
 */
class SystemUpdate
{
    const CHECK_CACHE_KEY = 'system_update:check';
    const CHECK_CACHE_SECONDS = 600;

    // 状态文件中 running 超过该时长视为异常中断（updater 被强杀等），允许重新发起
    const STALE_SECONDS = 3600;

    // 单次读取日志的最大字节数
    const LOG_CHUNK_BYTES = 65536;

    /**
     * 是否允许在线更新（运维可通过 .env ONLINE_UPDATE=false 关闭）
     */
    public static function enabled(): bool
    {
        return (bool) config('dootask.online_update');
    }

    /**
     * 检查更新
     * @param bool $force 跳过缓存
     * @return array
     */
    public static function check(bool $force = false): array
    {
        $base = [
            'supported' => false,
            'reason' => '',
            'branch' => '',
            'current_version' => Base::getVersion(),
            'latest_version' => '',
            'behind' => 0,
            'has_update' => false,
            'has_local_changes' => false,
            'has_db_changes' => false,
            'changelog' => '',
            'checked_at' => time(),
        ];
        if (!self::enabled()) {
            return array_merge($base, ['reason' => 'disabled']);
        }
        if (!$force && ($cached = Cache::get(self::CHECK_CACHE_KEY))) {
            return $cached;
        }
        $resp = self::appstore('check', [], 150);
        if ($resp['reason']) {
            return array_merge($base, ['reason' => $resp['reason']]);
        }
        $data = is_array($resp['data']) ? $resp['data'] : [];
        $result = array_merge($base, [
            'supported' => (bool) ($data['supported'] ?? false),
            'reason' => (string) ($data['reason'] ?? ''),
            'branch' => (string) ($data['branch'] ?? ''),
            'latest_version' => (string) ($data['latest_version'] ?? ''),
            'behind' => intval($data['behind'] ?? 0),
            'has_local_changes' => (bool) ($data['has_local_changes'] ?? false),
            'has_db_changes' => (bool) ($data['has_db_changes'] ?? false),
            'changelog' => trim((string) base64_decode((string) ($data['changelog_base64'] ?? ''))),
        ]);
        $result['has_update'] = $result['supported'] && $result['behind'] > 0;
        Cache::put(self::CHECK_CACHE_KEY, $result, self::CHECK_CACHE_SECONDS);
        return $result;
    }

    /**
     * 发起更新
     * @param User $user 发起的管理员
     * @param string $password 登录密码（二次确认）
     * @param string $version 前端确认时看到的目标版本，防止确认期间远端又有变化
     * @return array
     */
    public static function start(User $user, string $password, string $version): array
    {
        if (!self::enabled()) {
            throw new ApiException('在线更新已关闭');
        }
        if ($password === '') {
            throw new ApiException('请输入登录密码');
        }
        if ($user->password != Doo::md5s($password, $user->encrypt)) {
            throw new ApiException('密码错误');
        }
        if (self::isRunning(self::readState())) {
            throw new ApiException('系统正在更新中');
        }
        $check = self::check(true);
        if (!$check['supported']) {
            throw new ApiException('当前环境不支持在线更新');
        }
        if ($check['has_local_changes']) {
            throw new ApiException('服务器存在本地修改，无法在线更新');
        }
        if (!$check['has_update']) {
            throw new ApiException('系统已是最新版本');
        }
        if ($version !== '' && $version !== $check['latest_version']) {
            throw new ApiException('最新版本已变化，请重新检查更新');
        }

        $id = date('YmdHis');
        $resp = self::appstore('start', ['id' => $id, 'operator' => $user->userid], 30);
        if ($resp['reason']) {
            throw new ApiException($resp['reason'] === 'running' ? '系统正在更新中' : '发起系统更新失败');
        }
        Cache::forget(self::CHECK_CACHE_KEY);

        // 通知所有在线成员：系统即将短暂不可用
        PushTask::push([
            'fd' => WebSocket::pluck('fd')->toArray(),
            'msg' => [
                'type' => 'systemUpdate',
                'data' => [
                    'id' => $id,
                    'version' => $check['latest_version'],
                ],
            ],
        ]);

        return [
            'id' => $id,
            'from_version' => $check['current_version'],
            'to_version' => $check['latest_version'],
        ];
    }

    /**
     * 更新进度
     * @param int $offset 日志读取偏移（字节）
     * @return array
     */
    public static function status(int $offset = 0): array
    {
        $state = self::readState();
        if (empty($state)) {
            return ['status' => 'idle', 'log' => '', 'offset' => 0];
        }
        if (self::isStale($state)) {
            $state['status'] = 'failed';
            $state['error'] = $state['error'] ?: 'timeout';
        }
        $log = '';
        $logFile = (string) ($state['log'] ?? '');
        if (preg_match('/^docker\/logs\/update\/\d{14}\.log$/', $logFile) && file_exists(base_path($logFile))) {
            $size = filesize(base_path($logFile));
            $offset = max(0, min($offset, $size));
            if ($size > $offset) {
                $log = (string) file_get_contents(base_path($logFile), false, null, $offset, self::LOG_CHUNK_BYTES);
                $offset += strlen($log);
                // 去掉终端颜色码
                $log = preg_replace('/\e\[[0-9;]*m/', '', $log);
            }
        }
        $state['current_version'] = Base::getVersion();
        $state['log'] = $log;
        $state['offset'] = $offset;
        return $state;
    }

    /**
     * 读取状态文件
     * @return array
     */
    private static function readState(): array
    {
        $file = base_path('docker/logs/update/state.json');
        if (!file_exists($file)) {
            return [];
        }
        $state = Base::json2array(file_get_contents($file));
        return is_array($state) ? $state : [];
    }

    private static function isRunning(array $state): bool
    {
        return ($state['status'] ?? '') === 'running' && !self::isStale($state);
    }

    private static function isStale(array $state): bool
    {
        return ($state['status'] ?? '') === 'running'
            && time() - intval($state['started_at'] ?? 0) > self::STALE_SECONDS;
    }

    /**
     * 调用 appstore 内部接口
     * @param string $action check|start
     * @param array $payload
     * @param int $timeout
     * @return array ['reason' => 失败原因（空为成功）, 'data' => 接口 data]
     */
    private static function appstore(string $action, array $payload, int $timeout): array
    {
        $appKey = config('app.key') ?: '';
        if (empty($appKey)) {
            return ['reason' => 'appstore_unavailable', 'data' => null];
        }
        $resp = Ihttp::ihttp_request(
            "http://appstore/api/v1/internal/system/update/{$action}",
            json_encode($payload ?: new \stdClass()),
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . md5($appKey),
                'Version' => Base::getVersion(),
            ],
            $timeout
        );
        if (Base::isError($resp)) {
            info('[system_update] appstore request fail', ['action' => $action, 'error' => $resp]);
            return ['reason' => 'appstore_unavailable', 'data' => null];
        }
        // 旧版 appstore 没有该接口
        if (intval($resp['msg']) === 404) {
            return ['reason' => 'appstore_outdated', 'data' => null];
        }
        $body = Base::json2array($resp['data']);
        if (intval($body['code'] ?? 0) !== 200) {
            info('[system_update] appstore response error', ['action' => $action, 'body' => $resp['data']]);
            $reason = (string) ($body['data']['reason'] ?? '');
            return ['reason' => in_array($reason, ['running', 'unsupported']) ? $reason : 'appstore_error', 'data' => null];
        }
        return ['reason' => '', 'data' => $body['data'] ?? null];
    }
}
