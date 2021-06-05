<?php

namespace App\Tasks;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;

@error_reporting(E_ALL & ~E_NOTICE);


/**
 * 消息通知任务
 * Class WebSocketDialogMsgTask
 * @package App\Tasks
 */
class WebSocketDialogMsgTask extends AbstractTask
{
    protected $userid;
    protected $dialogMsgArray;

    /**
     * NoticeTask constructor.
     */
    public function __construct($userid, array $dialogMsgArray)
    {
        $this->userid = $userid;
        $this->dialogMsgArray = $dialogMsgArray;
    }

    /**
     * @throws \Throwable
     */
    public function start()
    {
        $userids = is_array($this->userid) ? $this->userid : [$this->userid];
        $msgId = intval($this->dialogMsgArray['id']);
        $send = intval($this->dialogMsgArray['send']);
        $dialogId = intval($this->dialogMsgArray['dialog_id']);
        if (empty($userids) || empty($msgId)) {
            return;
        }
        $pushIds = [];
        foreach ($userids AS $userid) {
            $msgRead = WebSocketDialogMsgRead::createInstance([
                'dialog_id' => $dialogId,
                'msg_id' => $msgId,
                'userid' => $userid,
            ]);
            try {
                $msgRead->saveOrFail();
                $pushIds[] = $userid;
            } catch (\Throwable $e) {
                //
            }
        }
        // 更新已发送数量
        if ($send != count($pushIds)) {
            $send = WebSocketDialogMsgRead::whereMsgId($msgId)->count();
            WebSocketDialogMsg::whereId($msgId)->update([ 'send' => $send ]);
            $this->dialogMsgArray['send'] = $send;
        }
        // 开始推送消息
        if ($pushIds) {
            PushTask::push([
                'userid' => $pushIds,
                'msg' => [
                    'type' => 'dialog',
                    'data' => $this->dialogMsgArray,
                ]
            ]);
        }
    }
}
