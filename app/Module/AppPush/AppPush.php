<?php

namespace App\Module\AppPush;

use App\Module\Base;

class AppPush
{
    /** 系统配置：官方 DooPush 共享通道（租户），设备 ID 由租户绑定返回 */
    public const CHANNEL_SYSTEM = 'doopush_system';

    /** 自定义：管理员自行配置的 DooPush 应用 */
    public const CHANNEL_CUSTOM = 'doopush';

    /**
     * 推送模式：system（系统配置，默认）、custom（自定义）、close（关闭）
     */
    public static function mode(): string
    {
        $mode = Base::setting('appPushSetting')['doopush'] ?? '';
        return match ($mode) {
            'custom' => 'custom',
            'close' => 'close',
            default => 'system',
        };
    }

    public static function channel(): ?string
    {
        return match (self::mode()) {
            'system' => self::CHANNEL_SYSTEM,
            'custom' => self::CHANNEL_CUSTOM,
            default => null,
        };
    }

    public static function enabled(): bool
    {
        return self::mode() !== 'close';
    }

    /**
     * @param array|int $userids
     * @param array $msg [title, subtitle, body, description, extra, seconds, badge]
     */
    public static function pushToUsers(array|int $userids, array $msg): void
    {
        app(DooPushClient::class)->pushToUsers($userids, $msg);
    }
}
