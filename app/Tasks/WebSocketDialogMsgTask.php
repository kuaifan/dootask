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
 * 推送会话消息
 * Class WebSocketDialogMsgTask
 * @package App\Tasks
 */
class WebSocketDialogMsgTask extends AbstractTask
{
    protected $id;
    protected $ignoreFd;
    protected $msgNotExistRetry = false;    // 推送失败后重试
    protected $silence = false;             // 静默推送（1:前端不通知、2:App不推送）

    /**
     * WebSocketDialogMsgTask constructor.
     * @param int $id         消息ID
     * @param mixed $ignoreFd
     */
    public function __construct($id, $ignoreFd = null)
    {
        $this->id = $id;
        $this->ignoreFd = $ignoreFd === null ? Request::header('fd') : $ignoreFd;
    }

    /**
     * @param $ignoreFd
     */
    public function setIgnoreFd($ignoreFd)
    {
        $this->ignoreFd = $ignoreFd;
    }

    /**
     * @param bool $msgNotExistRetry
     */
    public function setMsgNotExistRetry(bool $msgNotExistRetry): void
    {
        $this->msgNotExistRetry = $msgNotExistRetry;
    }

    /**
     * @param bool $silence
     */
    public function setSilence(bool $silence): void
    {
        $this->silence = $silence;
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
            if ($this->msgNotExistRetry) {
                $task = new WebSocketDialogMsgTask($this->id, $this->ignoreFd || '');
                $task->delay(1);
                $this->addTask($task);
            }
            return;
        }
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }

        // 提及会员
        $mentions = [];
        if ($msg->type === 'text') {
            preg_match_all("/<span class=\"mention user\" data-id=\"(\d+)\">/", $msg->msg['text'], $matchs);
            if ($matchs) {
                $mentions = array_values(array_filter(array_unique($matchs[1])));
            }
        }

        // 将会话以外的成员加入会话内
        $userids = $dialog->dialogUser->pluck('userid')->toArray();
        $diffids = array_values(array_diff($mentions, $userids));
        if ($diffids) {
            // 仅(群聊)且(是群主或没有群主)才可以@成员以外的人
            if ($dialog->type === 'group' && in_array($dialog->owner_id, [0, $msg->userid])) {
                $dialog->joinGroup($diffids, $msg->userid);
                $dialog->pushMsg("groupJoin", null, $diffids);
                $userids = array_values(array_unique(array_merge($mentions, $userids)));
            }
        }

        // 推送目标①：会话成员/群成员
        $array = [];
        foreach ($userids AS $userid) {
            if ($userid == $msg->userid) {
                $array[$userid] = false;
            } else {
                $mention = array_intersect([0, $userid], $mentions) ? 1 : 0;
                WebSocketDialogMsgRead::createInstance([
                    'dialog_id' => $msg->dialog_id,
                    'msg_id' => $msg->id,
                    'userid' => $userid,
                    'mention' => $mention,
                ])->saveOrIgnore();
                $array[$userid] = $mention;
            }
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
                    'silence' => $this->silence ? 1 : 0,
                    'data' => array_merge($msg->toArray(), [
                        'mention' => $mention,
                    ]),
                ]
            ]);
        }
        // umeng推送app
        if (!$this->silence) {
            $umengUserid = $array;
            if (isset($umengUserid[$msg->userid])) {
                unset($umengUserid[$msg->userid]);
            }
            $umengUserid = array_keys($umengUserid);
            $umengTitle = User::userid2nickname($msg->userid);
            if ($dialog->type == 'group') {
                $umengTitle = "{$dialog->getGroupName()} ($umengTitle)";
            }
            $umengMsg = new PushUmengMsg($umengUserid, [
                'title' => $umengTitle,
                'body' => $msg->previewMsg(),
                'description' => "MID:{$msg->id}",
                'seconds' => 3600,
                'badge' => 1,
            ]);
            Task::deliver($umengMsg);
        }

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
                            'silence' => $this->silence ? 1 : 0,
                            'data' => $msg->toArray(),
                        ]
                    ]);
                }
            }
        }
    }
}
