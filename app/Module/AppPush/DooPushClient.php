<?php

namespace App\Module\AppPush;

use App\Models\AppPushDevice;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use App\Module\Ihttp;
use Carbon\Carbon;

class DooPushClient
{
    public function pushToUsers(array|int $userids, array $msg): void
    {
        try {
            $setting = Base::setting('appPushSetting');
            $appId = trim((string)($setting['doopush_app_id'] ?? ''));
            $secret = trim((string)($setting['doopush_app_secret'] ?? ''));
            if (($setting['doopush'] ?? 'close') !== 'open' || $appId === '' || $secret === '') {
                return;
            }
            $content = str_replace(["\r\n", "\r", "\n"], '', $msg['body'] ?? '');
            $title = trim(str_replace(["\r\n", "\r", "\n"], '', $msg['title'] ?? ''));
            $title = $title === '' ? trim($content) : $title;
            if ($title === '' || trim($content) === '') {
                return;
            }
            $extra = [];
            foreach (is_array($msg['extra'] ?? null) ? $msg['extra'] : [] as $key => $value) {
                $extra[$key] = is_scalar($value) || $value === null ? (string)$value : Base::array2json($value);
            }
            // DooPush 只接受 action/url/data 与厂商参数，自定义字段须以 JSON 字符串放在 data（客户端解析）。
            $payload = array_merge($extra ? ['data' => Base::array2json($extra)] : [], [
                'huawei' => ['importance' => 'NORMAL', 'category' => 'IM'],
                'honor' => ['importance' => 'NORMAL'],
                'oppo' => ['category' => 'IM'],
                // DooPush 的 vivo 通道使用 classification，而非 category。
                'vivo' => ['classification' => 1],
            ]);
            $baseUrl = rtrim(trim((string)($setting['doopush_base_url'] ?? '')), '/') ?: 'https://doopush.com/api/v1';
            $url = $baseUrl . '/apps/' . rawurlencode($appId) . '/push';
            $headers = ['Authorization' => 'Bearer ' . $secret, 'Content-Type' => 'application/json'];
            $userids = array_unique(array_map('intval', (array)$userids));
            foreach ($userids as $userid) {
                if ($userid <= 0) {
                    continue;
                }
                try {
                    // 按用户单独取最新5台，避免跨查询分块时重复发送或突破设备数限制。
                    $deviceIds = AppPushDevice::whereChannel('doopush')->whereUserid($userid)
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
                    $this->send($url, $headers, [
                        'title' => mb_substr($title, 0, 200),
                        'content' => $content,
                        'badge' => $badge,
                        'payload' => $payload,
                        'target' => ['type' => 'devices', 'device_ids' => $deviceIds],
                    ], $userid);
                } catch (\Throwable $e) {
                    info('[DooPush] user push failed: ' . $e->getMessage(), ['userid' => $userid]);
                }
            }
        } catch (\Throwable $e) {
            info('[DooPush] push failed: ' . $e->getMessage());
        }
    }

    private function send(string $url, array $headers, array $body, int $userid): void
    {
        // 首次发送 + 最多2次重试；不在长生命周期进程中保存待发送状态。
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $this->request($url, $headers, $body);
                $result = Base::json2array($response['data'] ?? '');
                $status = (int)($response['msg'] ?? 0);
                if (Base::isSuccess($response) && $status >= 200 && $status < 300 && ($result['code'] ?? 0) === 200) {
                    return;
                }
                info('[DooPush] send failed', [
                    'userid' => $userid,
                    'attempt' => $attempt,
                    'status' => $status,
                    'message' => mb_substr((string)($result['message'] ?? $response['msg'] ?? 'Invalid response'), 0, 500),
                ]);
            } catch (\Throwable $e) {
                info('[DooPush] send failed: ' . $e->getMessage(), ['userid' => $userid, 'attempt' => $attempt]);
            }
        }
    }

    protected function request(string $url, array $headers, array $body): array
    {
        return Ihttp::ihttp_request($url, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), array_merge($headers, [
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ]), 10);
    }
}
