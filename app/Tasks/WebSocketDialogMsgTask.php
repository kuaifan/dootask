<?php

namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use Request;


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
        global $_A;
        $_A = [
            '__fill_url_remote_url' => true,
        ];
        //
        $msg = WebSocketDialogMsg::find($this->id);
        if (empty($msg)) {
            return;
        }
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }

        // 推送目标①：群成员
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

        // 推送目标②：正在打开这个任务会话的会员
        if ($dialog->type == 'group' && $dialog->group_type == 'task') {
            $list = User::whereTaskDialogId($dialog->id)->pluck('userid')->toArray();
            if ($list) {
                $array = [];
                foreach ($list as $uid) {
                    if (!in_array($uid, $userids)) {
                        $array[] = $uid;
                    }
                }
                if ($array) {
                    PushTask::push([
                        'userid' => $array,
                        'ignoreFd' => $this->ignoreFd,
                        'msg' => [
                            'type' => 'dialog',
                            'mode' => 'chat',
                            'data' => $msg->toArray(),
                        ]
                    ]);
                }
            }
        }
    }
}
