<?php

namespace App\Module\AppPush;

use App\Exceptions\ApiException;
use App\Models\AppPushDevice;
use App\Models\Setting;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use App\Module\Ihttp;
use Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Carbon\Carbon;

class DooPushClient
{
    /** 系统配置的租户凭证（不经推送设置接口下发前端） */
    private const TENANT_SETTING = 'appPushTenant';

    public function pushToUsers(array|int $userids, array $msg): void
    {
        try {
            $channel = AppPush::channel();
            if ($channel === null) {
                return;
            }
            $content = str_replace(["\r\n", "\r", "\n"], '', $msg['body'] ?? '');
            $title = trim(str_replace(["\r\n", "\r", "\n"], '', $msg['title'] ?? ''));
            $title = $title === '' ? trim($content) : $title;
            if ($title === '' || trim($content) === '') {
                return;
            }
            if ($channel === AppPush::CHANNEL_CUSTOM) {
                $setting = Base::setting('appPushSetting');
                $appId = trim((string)($setting['doopush_app_id'] ?? ''));
                $secret = trim((string)($setting['doopush_app_secret'] ?? ''));
                if ($appId === '' || $secret === '') {
                    return;
                }
                $baseUrl = rtrim(trim((string)($setting['doopush_base_url'] ?? '')), '/') ?: 'https://doopush.com/api/v1';
                $url = $baseUrl . '/apps/' . rawurlencode($appId) . '/push';
            } else {
                $tenant = $this->tenant();
                if (!$tenant) {
                    return;
                }
                $secret = $tenant['tenant_secret'];
                $url = $this->systemUrl('/tenant/push');
            }
            $extra = [];
            foreach (is_array($msg['extra'] ?? null) ? $msg['extra'] : [] as $key => $value) {
                $extra[$key] = is_scalar($value) || $value === null ? (string)$value : Base::array2json($value);
            }
            // DooPush 只接受 action/url/data 与厂商参数，自定义字段须以 JSON 字符串放在 data（客户端解析）。
            $payload = array_merge($extra ? ['data' => Base::array2json($extra)] : [], [
                'huawei' => ['importance' => 'NORMAL', 'category' => 'IM'],
                'honor' => ['importance' => 'NORMAL'],
                // OPPO 的 IM 类别属私信通道，需在 OPPO 后台申请权限，否则整条推送被拒（错误 67），暂不指定。
                // DooPush 的 vivo 通道使用 classification，而非 category。
                'vivo' => ['classification' => 1],
            ]);
            $headers = ['Authorization' => 'Bearer ' . $secret, 'Content-Type' => 'application/json'];
            $userids = array_unique(array_map('intval', (array)$userids));
            foreach ($userids as $userid) {
                if ($userid <= 0) {
                    continue;
                }
                try {
                    // 按用户单独取最新5台，避免跨查询分块时重复发送或突破设备数限制。
                    $deviceIds = AppPushDevice::whereChannel($channel)->whereUserid($userid)
                        ->where('updated_at', '>', Carbon::now()->subMonth())
                        ->orderByDesc('updated_at')->orderByDesc('id')->limit(5)
                        ->pluck('device_id')
                        // API 要求整数 ID，避免超长数字强转后意外指向另一台设备。
                        ->map(fn ($id) => filter_var(ltrim($id, '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))
                        ->filter(fn ($id) => $id !== false)->values()->all();
                    if (!$deviceIds) {
                        continue;
                    }
                    $badge = WebSocketDialogMsgRead::whereUserid($userid)->whereSilence(0)->whereReadAt(null)->count();
                    $body = [
                        'title' => mb_substr($title, 0, 200),
                        'content' => $content,
                        'badge' => $badge,
                        'payload' => $payload,
                    ];
                    if ($channel === AppPush::CHANNEL_CUSTOM) {
                        $this->send('POST', $url, $headers, $body + ['target' => ['type' => 'devices', 'device_ids' => $deviceIds]], $userid);
                        continue;
                    }
                    [$status, $result] = $this->send('POST', $url, $headers, $body + ['device_ids' => $deviceIds], $userid);
                    if ($status === 200) {
                        // 设备已被其他租户（其他服务器）绑定，本地不再推送。
                        $unbound = array_map('strval', (array)($result['data']['unbound_device_ids'] ?? []));
                        if ($unbound) {
                            AppPushDevice::whereChannel($channel)->whereIn('device_id', $unbound)->delete();
                        }
                    } elseif ($status === 401) {
                        // 租户已失效，下次绑定时重新注册；本轮不再继续请求。
                        $this->forgetTenant($secret);
                        return;
                    } elseif ($status === 403 || $status === 429) {
                        // 租户被禁用或超出今日配额，本轮其余用户同样会失败。
                        return;
                    }
                } catch (\Throwable $e) {
                    info('[DooPush] user push failed: ' . $e->getMessage(), ['userid' => $userid]);
                }
            }
        } catch (\Throwable $e) {
            info('[DooPush] push failed: ' . $e->getMessage());
        }
    }

    /**
     * 系统配置：按推送 Token 将设备绑定到本站租户，返回 DooPush 设备ID
     */
    public function bindDevice(string $pushToken): string
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $tenant = $this->tenant(true);
            if (!$tenant) {
                throw new ApiException('推送服务注册失败，请稍后再试');
            }
            [$status, $result] = $this->send('POST', $this->systemUrl('/tenant/devices'), [
                'Authorization' => 'Bearer ' . $tenant['tenant_secret'],
                'Content-Type' => 'application/json',
            ], ['token' => $pushToken]);
            $deviceId = (string)($result['data']['device_id'] ?? '');
            if ($status === 200 && preg_match('/^[1-9][0-9]{0,63}$/D', $deviceId)) {
                return $deviceId;
            }
            if ($status === 401) {
                // 租户已被删除或密钥失效：重新注册后再试一次。
                $this->forgetTenant($tenant['tenant_secret']);
                continue;
            }
            if ($status === 404) {
                throw new ApiException('推送设备未注册，请稍后再试');
            }
            if ($status === 403) {
                throw new ApiException('推送服务已被停用');
            }
            break;
        }
        throw new ApiException('推送设备绑定失败，请稍后再试');
    }

    /**
     * 系统配置：解除设备与本站租户的绑定（失败不影响退出登录）
     */
    public function unbindDevice(string $deviceId): void
    {
        try {
            $tenant = $this->tenant(false);
            if ($tenant) {
                // 在退出登录请求中同步执行：只请求一次且缩短超时，网络异常时不拖慢退出。
                $this->send('DELETE', $this->systemUrl('/tenant/devices/' . rawurlencode($deviceId)), [
                    'Authorization' => 'Bearer ' . $tenant['tenant_secret'],
                ], [], 0, 1, 3);
            }
        } catch (\Throwable $e) {
            info('[DooPush] unbind failed: ' . $e->getMessage());
        }
    }

    private function systemUrl(string $path): string
    {
        $config = config('dootask.doopush_system');
        return rtrim($config['base_url'], '/') . '/apps/' . rawurlencode((string)$config['app_id']) . $path;
    }

    /**
     * 读取租户凭证；切换官方地址或应用后视为未注册。$register 为 true 时按需自助注册。
     */
    private function tenant(bool $register = false): ?array
    {
        $tenant = $this->storedTenant();
        if ($tenant || !$register) {
            return $tenant;
        }
        // 多进程并发绑定时只注册一次，其余进程等待后复用。
        try {
            return Cache::lock('doopush:tenant-register', 30)->block(15, function () {
                return $this->storedTenant() ?: $this->registerTenant();
            });
        } catch (LockTimeoutException) {
            return $this->storedTenant();
        }
    }

    private function storedTenant(): ?array
    {
        // 直接读库：凭证可能刚被其他进程更新，不使用请求级缓存。
        $tenant = Base::string2array(Setting::whereName(self::TENANT_SETTING)->value('setting'));
        $config = config('dootask.doopush_system');
        if (empty($tenant['tenant_secret'])
            || ($tenant['base_url'] ?? '') !== $config['base_url']
            || ($tenant['app_id'] ?? '') !== (string)$config['app_id']) {
            return null;
        }
        return $tenant;
    }

    private function registerTenant(): ?array
    {
        $config = config('dootask.doopush_system');
        [$status, $result] = $this->send('POST', $this->systemUrl('/tenants'), [
            'X-App-Key' => $config['app_key'],
            'Content-Type' => 'application/json',
        ], ['name' => 'DooTask', 'site_url' => mb_substr(Base::getSchemeAndHost(), 0, 255)]);
        $secret = (string)($result['data']['tenant_secret'] ?? '');
        if ($status !== 201 || $secret === '') {
            info('[DooPush] tenant register failed', ['status' => $status, 'message' => $result['message'] ?? '']);
            return null;
        }
        $tenant = [
            'base_url' => $config['base_url'],
            'app_id' => (string)$config['app_id'],
            'tenant_id' => (int)($result['data']['tenant_id'] ?? 0),
            'tenant_secret' => $secret,
        ];
        Base::setting(self::TENANT_SETTING, $tenant);
        return $tenant;
    }

    private function forgetTenant(string $secret): void
    {
        $tenant = $this->storedTenant();
        // 仅清理失效的那份凭证，避免覆盖其他进程刚注册的新凭证。
        if ($tenant && $tenant['tenant_secret'] === $secret) {
            Base::setting(self::TENANT_SETTING, []);
        }
    }

    /**
     * 发送请求，网络错误或 5xx 时最多重试2次；返回 [HTTP 状态码（网络失败为 0）, 响应 JSON]
     */
    private function send(string $method, string $url, array $headers, array $body, int $userid = 0, int $attempts = 3, int $timeout = 10): array
    {
        $status = 0;
        $result = [];
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->request($url, $headers, $body, $method, $timeout);
                $result = Base::json2array($response['data'] ?? '');
                $status = Base::isSuccess($response) ? (int)($response['msg'] ?? 0) : 0;
                if ($status >= 200 && $status < 300 && in_array($result['code'] ?? 0, [200, 201], true)) {
                    return [$status, $result];
                }
                info('[DooPush] send failed', [
                    'userid' => $userid,
                    'attempt' => $attempt,
                    'status' => $status,
                    'message' => mb_substr((string)($result['message'] ?? $response['msg'] ?? 'Invalid response'), 0, 500),
                ]);
                if ($status >= 400 && $status < 500 && isset($result['code'])) {
                    // 业务拒绝（鉴权、参数、配额等）重试无意义。
                    return [$status, $result];
                }
            } catch (\Throwable $e) {
                $status = 0;
                info('[DooPush] send failed: ' . $e->getMessage(), ['userid' => $userid, 'attempt' => $attempt]);
            }
        }
        // 2xx 但响应无效时按网络失败处理。
        return [$status >= 200 && $status < 300 ? 0 : $status, $result];
    }

    protected function request(string $url, array $headers, array $body, string $method = 'POST', int $timeout = 10): array
    {
        $post = $method === 'POST' ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : '';
        $options = [
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ];
        if ($method !== 'POST') {
            $options['CURLOPT_CUSTOMREQUEST'] = $method;
        }
        return Ihttp::ihttp_request($url, $post, array_merge($headers, $options), $timeout);
    }
}
