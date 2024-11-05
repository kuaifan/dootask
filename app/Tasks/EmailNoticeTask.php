<?php

namespace App\Tasks;

use App\Models\Setting;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use App\Module\Doo;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class EmailNoticeTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        $setting = Base::setting('emailSetting');
        // 消息通知
        if ($setting['notice_msg'] === 'open') {
            $userMinute = intval($setting['msg_unread_user_minute']);
            $groupMinute = intval($setting['msg_unread_group_minute']);
            \DB::statement("SET SQL_MODE=''");
            $builder = WebSocketDialogMsg::select(['web_socket_dialog_msgs.*', 'r.id as r_id', 'r.userid as r_userid'])
                ->join('web_socket_dialog_msg_reads as r', 'web_socket_dialog_msgs.id', '=', 'r.msg_id')
                ->whereNull("r.read_at")
                ->where("r.silence", 0)
                ->where("r.email", 0);
            if ($userMinute > -1) {
                $builder->clone()
                    ->where("web_socket_dialog_msgs.dialog_type", "user")
                    ->whereIn("web_socket_dialog_msgs.type", ["text", "file", "record", "meeting"])
                    ->whereBetween("web_socket_dialog_msgs.created_at", [
                        Carbon::now()->subMinutes($userMinute + 10),
                        Carbon::now()->subMinutes($userMinute)
                    ])
                    ->groupBy('r_userid')
                    ->chunkById(100, function ($rows) {
                        $this->unreadMsgEmail($rows, "user");
                    });
            }
            if ($groupMinute > -1) {
                $builder->clone()
                    ->where("web_socket_dialog_msgs.dialog_type", "group")
                    ->whereIn("web_socket_dialog_msgs.type", ["text", "file", "record", "meeting"])
                    ->whereBetween("web_socket_dialog_msgs.created_at", [
                        Carbon::now()->subMinutes($groupMinute + 10),
                        Carbon::now()->subMinutes($groupMinute)
                    ])
                    ->groupBy('r_userid')
                    ->chunkById(100, function ($rows) {
                        $this->unreadMsgEmail($rows, "group");
                    });
            }
        }
    }

    public function end()
    {

    }

    /**
     * 未读消息通知
     * @param $rows
     * @param $dialogType
     * @return void
     */
    private function unreadMsgEmail($rows, $dialogType)
    {
        $array = $rows->groupBy('r_userid');
        foreach ($array as $userid => $data) {
            $data = WebSocketDialogMsg::select(['web_socket_dialog_msgs.*', 'r.id as r_id', 'r.userid as r_userid'])
                ->join('web_socket_dialog_msg_reads as r', 'web_socket_dialog_msgs.id', '=', 'r.msg_id')
                ->whereNull("r.read_at")
                ->where("r.silence", 0)
                ->where("r.email", 0)
                ->where("r.userid", $userid)
                ->where("web_socket_dialog_msgs.dialog_type", $dialogType)
                ->whereIn("web_socket_dialog_msgs.type", ["text", "file", "record", "meeting"])
                ->take(100)
                ->get();
            if (empty($data)) {
                continue;
            }
            $user = User::whereBot(0)->whereNull('disable_at')->find($userid);
            if (empty($user)) {
                continue;
            }
            if (!Base::isEmail($user->email)) {
                continue;
            }
            $setting = Base::setting('emailSetting');
            $msgType = $dialogType === "group" ? "群聊" : "成员";
            $subject = null;
            $content = view('email.unread', [
                'type' => 'head',
                'nickname' => $user->nickname,
                'msgType' => $msgType,
                'count' => count($data),
            ])->render();
            $lists = $data->groupBy('dialog_id');
            /** @var WebSocketDialogMsg[] $items */
            foreach ($lists as $items) {
                $dialogId = 0;
                $dialogName = null;
                foreach ($items as $item) {
                    $item->cancelAppend();
                    $item->userInfo = User::userid2basic($item->userid, ['lang']);
                    Doo::setLanguage($item->userInfo->lang);
                    $item->preview = WebSocketDialogMsg::previewMsg($item, true);
                    $item->preview = str_replace('<p>', '<p style="margin:0;padding:0">', $item->preview);
                    if (empty($dialogId)) {
                        $dialogId = $item->dialog_id;
                    }
                    if ($dialogName === null) {
                        if ($dialogType === "user" && $item->userInfo) {
                            if ($item->userInfo->profession) {
                                $dialogName = $item->userInfo->nickname . " ({$item->userInfo->profession})";
                            } else {
                                $dialogName = $item->userInfo->nickname;
                            }
                        } else {
                            $dialogName = $item->webSocketDialog?->getGroupName();
                        }
                    }
                }
                if ($subject === null) {
                    $count = count($lists);
                    if ($count > 1) {
                        $subject = "来自{$count}个{$msgType}未读消息提醒";
                    } else {
                        $subject = "来自{$dialogName}未读消息提醒";
                    }
                }
                $content .= view('email.unread', [
                    'type' => 'content',
                    'dialogUrl' => config("app.url") . "/manage/messenger?dialog_id={$dialogId}",
                    'dialogName' => $dialogName,
                    'unread' => count($items),
                    'items' => $items,
                ])->render();
                $content = str_replace("{{RemoteURL}}", config("app.url") . "/", $content);
            }
            try {
                Setting::validateAddr($user->email, function($to) use ($content, $subject, $setting) {
                    Factory::mailer()
                        ->setDsn("smtp://{$setting['account']}:{$setting['password']}@{$setting['smtp_server']}:{$setting['port']}?verify_peer=0")
                        ->setMessage(EmailMessage::create()
                            ->from(env('APP_NAME', 'Task') . " <{$setting['account']}>")
                            ->to($to)
                            ->subject($subject)
                            ->html($content))
                        ->send();
                });
            } catch (\Throwable $e) {
                info("unreadMsgEmail: " . $e->getMessage());
            }
            WebSocketDialogMsgRead::whereIn('id', $data->pluck('r_id'))->update([
                'email' => 1
            ]);
        }
    }
}
