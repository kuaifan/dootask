<?php

namespace App\Module\AppPush;

use App\Models\UmengAlias;
use App\Module\Base;

class AppPush
{
    public static function enabled(): bool
    {
        $setting = Base::setting('appPushSetting');
        return ($setting['push'] ?? 'close') === 'open' || ($setting['doopush'] ?? 'close') === 'open';
    }

    /**
     * @param array|int $userids
     * @param array $msg [title, subtitle, body, description, extra, seconds, badge]
     */
    public static function pushToUsers(array|int $userids, array $msg): void
    {
        $setting = Base::setting('appPushSetting');
        if (($setting['push'] ?? 'close') === 'open') {
            try {
                UmengAlias::pushMsgToUserid($userids, $msg);
            } catch (\Throwable $e) {
                info('[AppPush] Umeng failed: ' . $e->getMessage());
            }
        }
        app(DooPushClient::class)->pushToUsers($userids, $msg);
    }
}
