<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * DooPush 服务端 HTTP 客户端封装：
 *
 * - 发推送：POST /apps/{appId}/push
 * - 设设备标签：POST /apps/{appId}/device-tags/{deviceToken}
 * - 删设备标签：DELETE /apps/{appId}/device-tags/{deviceToken}/{tagName}
 *
 * 鉴权使用 X-API-Key（DooPush 文档推荐 Header 方式）。
 * 配置来源：config('dootask.doopush')。未启用或未配置时由调用方判断；本类不做静默降级。
 */
class DooPushClient
{
    public static function enabled(): bool
    {
        $cfg = (array) config('dootask.doopush', []);
        return ($cfg['enabled'] ?? false)
            && !empty($cfg['app_id'])
            && !empty($cfg['api_key']);
    }

    /**
     * 推送给若干 userid（按 userid 标签 OR 并集定向）。
     *
     * @param array<int> $userids
     * @param array{title:string, body?:string, badge?:int, extra?:array} $msg
     */
    public static function pushToUserids(array $userids, array $msg, ?string $platform = null): Response
    {
        $cfg = (array) config('dootask.doopush', []);
        $tagName = (string) ($cfg['tag_name'] ?? 'userid');

        $tags = [];
        foreach (array_unique($userids) as $uid) {
            $tags[] = ['tag_name' => $tagName, 'tag_value' => (string) $uid];
        }

        $target = ['type' => 'tags', 'tags' => $tags];
        if ($platform !== null) {
            $target['platform'] = $platform;
        }

        $payload = [
            'title'   => (string) ($msg['title'] ?? ''),
            'content' => (string) ($msg['body'] ?? ''),
            'target'  => $target,
        ];

        if (isset($msg['badge'])) {
            $payload['badge'] = (int) $msg['badge'];
        }

        // 自定义载荷：保留 extra 中的 dialog_id / msg_id 等点击跳转字段
        $extra = (array) ($msg['extra'] ?? []);
        if (!empty($extra)) {
            $payload['payload'] = [
                'action' => 'open_dialog',
                'data'   => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ];
        }

        return self::http()->post(self::url('/push'), $payload);
    }

    /**
     * 给指定设备 token 绑定 userid 标签（App SDK 注册后由客户端→DooTask 后端→本接口完成）。
     */
    public static function setUserTag(string $deviceToken, int $userid): Response
    {
        $cfg = (array) config('dootask.doopush', []);
        $tagName = (string) ($cfg['tag_name'] ?? 'userid');

        return self::http()->post(self::url("/device-tags/{$deviceToken}"), [
            'tag_name'  => $tagName,
            'tag_value' => (string) $userid,
        ]);
    }

    /**
     * 解除设备的 userid 标签（用户登出/换号时调用）。
     */
    public static function unsetUserTag(string $deviceToken): Response
    {
        $cfg = (array) config('dootask.doopush', []);
        $tagName = (string) ($cfg['tag_name'] ?? 'userid');

        return self::http()->delete(self::url("/device-tags/{$deviceToken}/{$tagName}"));
    }

    private static function http()
    {
        $cfg = (array) config('dootask.doopush', []);
        return Http::withHeaders([
            'X-API-Key'    => (string) ($cfg['api_key'] ?? ''),
            'Content-Type' => 'application/json',
        ])->timeout(10);
    }

    private static function url(string $path): string
    {
        $cfg = (array) config('dootask.doopush', []);
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? 'https://doopush.com/api/v1'), '/');
        $appId   = (string) ($cfg['app_id'] ?? '');
        return $baseUrl . '/apps/' . $appId . $path;
    }
}
