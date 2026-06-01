<?php

namespace App\Tasks;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgTodo;
use App\Module\Doo;
use Carbon\Carbon;

/**
 * 待办提醒：到点由 todo-alert 机器人在原会话发一条「引用原消息 + @被指派成员」的普通文本
 * （同一消息同批到点的成员合并一条）。
 */
class TodoRemindTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 构造提醒文本：每个被提醒成员一个 @ span + 提示语。
     * 直接拼 <span class="mention user" data-id> 是因为 sendMsg 不会调用 formatMsg，
     * 文本会原样入库，msgJoinGroup 据此 span 正则提取 @。
     */
    public static function buildRemindText(array $mentionUserids): string
    {
        $nicknames = User::whereIn('userid', $mentionUserids)->pluck('nickname', 'userid');
        $mentionText = '';
        foreach ($mentionUserids as $uid) {
            $name = $nicknames[$uid] ?? $uid;
            $mentionText .= "<span class=\"mention user\" data-id=\"{$uid}\">@{$name}</span> ";
        }
        return $mentionText . Doo::translate('你有一条待办到提醒时间啦');
    }

    public function start()
    {
        $rows = WebSocketDialogMsgTodo::dueReminders();
        if ($rows->isEmpty()) {
            return;
        }
        $botUser = User::botGetOrCreate('todo-alert');
        if (empty($botUser)) {
            return;
        }
        foreach ($rows->groupBy('msg_id') as $msgId => $group) {
            $rowIds = $group->pluck('id')->toArray();
            $userids = $group->pluck('userid')->map('intval')->values()->toArray();
            //
            $msg = WebSocketDialogMsg::find($msgId);
            $dialog = $msg ? WebSocketDialog::find($msg->dialog_id) : null;
            if (empty($msg) || empty($dialog)) {
                // 原消息/会话已不存在：标记已提醒，避免空转重复扫描
                WebSocketDialogMsgTodo::whereIn('id', $rowIds)->update(['reminded_at' => Carbon::now()]);
                continue;
            }
            //
            $memberIds = $dialog->dialogUser->pluck('userid')->map('intval')->values()->toArray();
            $mentionUserids = array_values(array_intersect($userids, $memberIds));
            if (empty($mentionUserids)) {
                // 被指派人都已退群：没人可 @，标记已提醒避免空转重复扫描
                WebSocketDialogMsgTodo::whereIn('id', $rowIds)->update(['reminded_at' => Carbon::now()]);
                continue;
            }
            $res = WebSocketDialogMsg::sendMsg(
                "reply-{$msg->id}",                          // 引用原消息 → reply_data 自动填充
                $dialog->id,
                'text',                                      // 普通文本
                ['text' => self::buildRemindText($mentionUserids)],
                $botUser->userid,
                false, false, false                          // push_self / push_retry / push_silence
            );
            //
            if (\App\Module\Base::isSuccess($res)) {
                WebSocketDialogMsgTodo::whereIn('id', $rowIds)->update(['reminded_at' => Carbon::now()]);
            }
        }
    }

    public function end()
    {
    }
}
