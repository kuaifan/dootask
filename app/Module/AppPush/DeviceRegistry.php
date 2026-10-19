<?php

namespace App\Module\AppPush;

use App\Exceptions\ApiException;
use App\Models\AppPushDevice;
use App\Models\UserDevice;

class DeviceRegistry
{
    /**
     * 绑定当前登录设备；系统配置下先按推送 Token 绑定到本站租户，以 DooPush 返回的设备ID为准。
     */
    public static function update(int $userid, array $attributes, string $pushToken = ''): void
    {
        $hash = UserDevice::check();
        if (!$hash) {
            throw new ApiException('身份已失效,请重新登录', [], -1);
        }
        $channel = AppPush::channel();
        if ($channel === null) {
            return;
        }
        if ($channel === AppPush::CHANNEL_SYSTEM) {
            if ($pushToken === '') {
                throw new ApiException('推送Token不能为空');
            }
            $attributes['device_id'] = app(DooPushClient::class)->bindDevice($pushToken);
        }
        $device = AppPushDevice::createInstance(array_merge($attributes, [
            'userid' => $userid,
            'channel' => $channel,
            'device_hash' => $hash,
        ]));
        // 唯一键原子 upsert，设备切换帐号或并发上报时保留一条绑定。
        AppPushDevice::upsert([$device->getAttributes()], ['channel', 'device_id'], [
            'userid', 'vendor', 'platform', 'device_hash', 'device', 'version', 'ua', 'is_notified', 'updated_at',
        ]);
    }

    /**
     * 退出时 token 可以已失效，但必须与绑定时的 token 相同。
     */
    public static function remove(string $deviceId, string $token): void
    {
        if ($token === '') {
            return;
        }
        // 与 UserDevice::check()/forget() 共用 TOKEN MD5 算法，不依赖 token 解码。
        self::forgetHash(md5($token), $deviceId);
    }

    /**
     * 按登录 token 哈希清理推送设备（退出登录、踢下线等），系统配置的设备同时解除租户绑定
     */
    public static function forgetHash(string $hash, ?string $deviceId = null): void
    {
        $query = AppPushDevice::whereIn('channel', [AppPush::CHANNEL_SYSTEM, AppPush::CHANNEL_CUSTOM])->whereDeviceHash($hash)
            ->when($deviceId !== null, fn ($q) => $q->whereDeviceId($deviceId));
        $systemIds = (clone $query)->whereChannel(AppPush::CHANNEL_SYSTEM)->pluck('device_id')->all();
        $query->delete();
        foreach ($systemIds as $id) {
            app(DooPushClient::class)->unbindDevice($id);
        }
    }
}
