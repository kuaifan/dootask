<?php

namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use Hhxsv5\LaravelS\Swoole\Task\Task;
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
        $array = [];
        $userids = $dialog->dialogUser->pluck('userid')->toArray();
        foreach ($userids AS $userid) {
            if ($userid == $msg->userid) {
                continue;
            }
            $mention = preg_match("/<span class=\"mention user\" data-id=\"[0|{$userid}]\">/", $msg->type === 'text' ? $msg->msg['text'] : '');
            WebSocketDialogMsgRead::createInstance([
                'dialog_id' => $msg->dialog_id,
                'msg_id' => $msg->id,
                'userid' => $userid,
                'mention' => $mention,
            ])->saveOrIgnore();
            $array[$userid] = $mention;
        }
        // 更新已发送数量
        $msg->send = WebSocketDialogMsgRead::whereMsgId($msg->id)->count();
        $msg->save();
        // 开始推送消息
        foreach ($array as $userid => $mention) {
            PushTask::push([
                'userid' => $userid,
                'ignoreFd' => $this->ignoreFd,
                'msg' => [
                    'type' => 'dialog',
                    'mode' => 'add',
                    'data' => array_merge($msg->toArray(), [
                        'mention' => $mention,
                    ]),
                ]
            ]);
        }
        // umeng推送app
        $msgTitle = User::userid2nickname($msg->userid);
        if ($dialog->type == 'group') {
            $msgTitle = "{$dialog->name} ($msgTitle)";
        }
        $umengMsg = new PushUmengMsg(array_keys($array), [
            'title' => $msgTitle,
            'body' => $msg->previewMsg(),
            'description' => "消息推送-ID:{$msg->id}",
            'seconds' => 3600,
            'badge' => 1,
        ]);
        Task::deliver($umengMsg);

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
