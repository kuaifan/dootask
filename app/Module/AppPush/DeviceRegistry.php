<?php

namespace App\Module\AppPush;

use App\Exceptions\ApiException;
use App\Models\AppPushDevice;
use App\Models\UmengAlias;
use App\Models\UserDevice;

class DeviceRegistry
{
    /**
     * 绑定当前登录设备，并在同一事务中清理旧壳别名。
     */
    public static function update(int $userid, array $attributes, string $uniqueId = ''): void
    {
        $hash = UserDevice::check();
        if (!$hash) {
            throw new ApiException('身份已失效,请重新登录', [], -1);
        }
        AppPushDevice::transaction(function () use ($userid, $attributes, $uniqueId, $hash) {
            $device = AppPushDevice::createInstance(array_merge($attributes, [
                'userid' => $userid,
                'channel' => 'doopush',
                'device_hash' => $hash,
            ]));
            // 唯一键原子 upsert，设备切换帐号或并发上报时保留一条绑定。
            AppPushDevice::upsert([$device->getAttributes()], ['channel', 'device_id'], [
                'userid', 'vendor', 'platform', 'device_hash', 'device', 'version', 'ua', 'is_notified', 'updated_at',
            ]);
            UmengAlias::whereDeviceHash($hash)->delete();
            if (mb_strlen($uniqueId) >= 5) {
                // 显式使用 ! 转义，避免 %、_ 或反斜杠扩大别名匹配范围。
                $suffix = strtr($uniqueId, ['!' => '!!', '%' => '!%', '_' => '!_']);
                UmengAlias::whereRaw("alias LIKE ? ESCAPE '!'", ['%-' . $suffix])->delete();
            }
        });
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
        AppPushDevice::whereChannel('doopush')->whereDeviceId($deviceId)->whereDeviceHash(md5($token))->delete();
    }
}
