<?php

namespace App\Tasks;

use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use Request;

@error_reporting(E_ALL & ~E_NOTICE);


/**
 * 推送回话消息
 * Class WebSocketDialogMsgTask
 * @package App\Tasks
 */
class WebSocketDialogMsgTask extends AbstractTask
{
    protected $id;
    protected $ignoreFd;

    /**
     * WebSocketDialogMsgTask constructor.
     * @param int $id         消息ID
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->ignoreFd = Request::header('fd');
    }

    public function start()
    {
        $msg = WebSocketDialogMsg::find($this->id);
        if (empty($msg)) {
            return;
        }
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }
        // 推送目标
        $userids = $dialog->dialogUser->pluck('userid')->toArray();
        foreach ($userids AS $userid) {
            if ($userid == $msg->userid) {
                continue;
            }
            WebSocketDialogMsgRead::createInstance([
                'dialog_id' => $msg->dialog_id,
                'msg_id' => $msg->id,
                'userid' => $userid,
            ])->saveOrIgnore();
        }
        // 更新已发送数量
        $msg->send = WebSocketDialogMsgRead::whereMsgId($msg->id)->count();
        $msg->save();
        // 开始推送消息
        PushTask::push([
            'userid' => $userids,
            'ignoreFd' => $this->ignoreFd,
            'msg' => [
                'type' => 'dialog',
                'mode' => 'add',
                'data' => $msg->toArray(),
            ]
        ]);
    }
}
