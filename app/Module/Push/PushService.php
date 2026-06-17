<?php

namespace App\Module\Push;

use App\Models\WebSocketDialogMsgRead;
use App\Services\DooPushClient;

/**
 * 推送业务编排（DooPush 通道）。
 *
 * UmengAlias::pushMsgToUserid 在 DooPushClient::enabled() 时委托到此处；
 * 旧 Umeng 链路仍在 UmengAlias 内部保留，不动 private 方法的可见性。
 */
class PushService
{
    /**
     * DooPush：按 userid 标签 OR 并集定向推送；badge 按每个 user 单独取未读数。
     *
     * DooPush /push 一次只能下发一个 badge，需按 badge 分组合并下发以减少 HTTP 次数。
     *
     * @param array<int>|int $userid
     * @param array{title?:string, body?:string, extra?:array} $array
     */
    public static function pushToUsers($userid, array $array): void
    {
        if (empty($userid) || empty($array)) {
            return;
        }

        $userids = is_array($userid) ? array_values(array_filter($userid)) : [(int) $userid];
        if (empty($userids)) {
            return;
        }

        // userid -> badge 数；按 badge 分组合并下发
        $byBadge = [];
        foreach ($userids as $uid) {
            $badge = WebSocketDialogMsgRead::whereUserid($uid)
                ->whereSilence(0)
                ->whereReadAt(null)
                ->count();
            $byBadge[$badge][] = (int) $uid;
        }

        foreach ($byBadge as $badge => $batch) {
            try {
                $msg = $array;
                $msg['badge'] = (int) $badge;
                $resp = DooPushClient::pushToUserids($batch, $msg);
                if (!$resp->successful()) {
                    info('[DooPush] non-2xx: ' . $resp->status() . ' ' . $resp->body());
                }
            } catch (\Throwable $e) {
                info('[DooPush] exception: ' . $e->getMessage());
            }
        }
    }
}
