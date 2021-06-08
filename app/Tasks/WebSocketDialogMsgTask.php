<?php

namespace App\Tasks;

use App\Models\WebSocket;
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
    protected $userid;
    protected $dialogMsgArray;
    protected $currentFd;

    /**
     * WebSocketDialogMsgTask constructor.
     * @param int|array $userid         发送对象ID 或 ID组
     * @param array $dialogMsgArray     发送的内容
     * @param null $currentFd           当前发送会员的 websocket fd （用于给其他设备发送消息，留空通过header获取）
     */
    public function __construct($userid, array $dialogMsgArray, $currentFd = null)
    {
        $this->userid = $userid;
        $this->dialogMsgArray = $dialogMsgArray;
        $this->currentFd = $currentFd ?: Request::header('fd');
    }

    public function start()
    {
        $userids = is_array($this->userid) ? $this->userid : [$this->userid];
        $msgId = intval($this->dialogMsgArray['id']);
        $send = intval($this->dialogMsgArray['send']);
        $dialogId = intval($this->dialogMsgArray['dialog_id']);
        if (empty($userids) || empty($msgId)) {
            return;
        }
        // 推送目标
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
        $fd = WebSocket::getOtherFd($this->currentFd);
        // 更新已发送数量
        if ($send != count($pushIds)) {
            $send = WebSocketDialogMsgRead::whereMsgId($msgId)->count();
            WebSocketDialogMsg::whereId($msgId)->update([ 'send' => $send ]);
            $this->dialogMsgArray['send'] = $send;
        }
        // 开始推送消息
        if ($pushIds || $fd) {
            PushTask::push([
                'userid' => $pushIds,
                'fd' => $fd,
                'msg' => [
                    'type' => 'dialog',
                    'mode' => 'add',
                    'data' => $this->dialogMsgArray,
                ]
            ]);
        }
    }
}
