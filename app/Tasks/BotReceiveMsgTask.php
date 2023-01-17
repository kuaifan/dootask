<?php

namespace App\Tasks;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/**
 * 推送会话消息
 * Class BotReceiveMsgTask
 * @package App\Tasks
 */
class BotReceiveMsgTask extends AbstractTask
{
    protected $userid;
    protected $msgId;

    public function __construct($userid, $msgId)
    {
        parent::__construct(...func_get_args());
        $this->userid = $userid;
        $this->msgId = $msgId;
    }

    public function start()
    {
        $botUser = User::whereUserid($this->userid)->whereBot(1)->first();
        if (empty($botUser)) {
            return;
        }
        $msg = WebSocketDialogMsg::find($this->msgId);
        if (empty($msg)) {
            return;
        }
        $msg->readSuccess($botUser->userid);
        //
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }
        if ($dialog->type !== 'user') {
            return;
        }
        if ($botUser->email === 'bot-manager@bot.system') {
            $this->botManagerReceive($msg);
        }
    }

    public function end()
    {

    }

    /**
     * 机器人管理处理消息
     * @param WebSocketDialogMsg $msg
     * @return void
     */
    private function botManagerReceive(WebSocketDialogMsg $msg)
    {
        if ($msg->type === 'text') {
            $text = trim(strip_tags($msg->msg['text']));
            if (empty($text)) {
                return;
            }
            $array = Base::newTrim(explode(" ", "{$text}    "));
            $type = $array[0];
            $data = [];
            $notice = "";
            switch ($type) {
                /**
                 * 列表
                 */
                case '/list':
                    $data = User::select(['users.*'])
                        ->join('user_bots', 'users.userid', '=', 'user_bots.bot_id')
                        ->where('users.bot', 1)
                        ->where('user_bots.userid', $msg->userid)
                        ->take(50)
                        ->orderByDesc('id')
                        ->get();
                    if ($data->isEmpty()) {
                        $type = "notice";
                        $notice = "您没有创建机器人。";
                    }
                    break;

                /**
                 * 创建
                 */
                case '/newbot':
                    if (User::select(['users.*'])
                            ->join('user_bots', 'users.userid', '=', 'user_bots.bot_id')
                            ->where('users.bot', 1)
                            ->where('user_bots.userid', $msg->userid)
                            ->count() >= 50) {
                        $type = "notice";
                        $notice = "超过最大创建数量。";
                        break;
                    }
                    if (strlen($array[1]) < 2 || strlen($array[1]) > 20) {
                        $type = "notice";
                        $notice = "机器人名称由2-20个字符组成。";
                        break;
                    }
                    $data = User::botGetOrCreate("user-" . Base::generatePassword(), [
                        'nickname' => $array[1]
                    ], $msg->userid);
                    if (empty($data)) {
                        $type = "notice";
                        $notice = "创建失败。";
                        break;
                    }
                    $dialog = WebSocketDialog::checkUserDialog($data->userid, $msg->userid);
                    if ($dialog) {
                        $text = "你好，我是你的机器人：{$data->nickname}, 我的机器人ID是：{$data->userid}";
                        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $data->userid);   // todo 未能在任务end事件来发送任务
                    }
                    break;

                /**
                 * 修改名字
                 */
                case '/setname':
                    if (strlen($array[2]) < 2 || strlen($array[2]) > 20) {
                        $type = "notice";
                        $notice = "机器人名称由2-20个字符组成。";
                        break;
                    }
                    $data = $this->botManagerOne($array[1], $msg->userid);
                    if ($data) {
                        $data->nickname = $array[2];
                        $data->az = Base::getFirstCharter($array[2]);
                        $data->pinyin = Base::cn2pinyin($array[2]);
                        $data->save();
                    } else {
                        $type = "notice";
                        $notice = "机器人不存在。";
                    }
                    break;


                /**
                 * 删除
                 */
                case '/deletebot':
                    $data = $this->botManagerOne($array[1], $msg->userid);
                    if ($data) {
                        $data->deleteUser('delete bot');
                    } else {
                        $type = "notice";
                        $notice = "机器人不存在。";
                    }
                    break;

                /**
                 * 获取Token
                 */
                case '/token':
                    $data = $this->botManagerOne($array[1], $msg->userid);
                    if ($data) {
                        User::token($data);
                    } else {
                        $type = "notice";
                        $notice = "机器人不存在。";
                    }
                    break;

                /**
                 * 更新Token
                 */
                case '/revoke':
                    $data = $this->botManagerOne($array[1], $msg->userid);
                    if ($data) {
                        $data->encrypt = Base::generatePassword(6);
                        $data->password = Base::md52(Base::generatePassword(32), $data->encrypt);
                        $data->save();
                    } else {
                        $type = "notice";
                        $notice = "机器人不存在。";
                    }
                    break;

                /**
                 * 会话搜索
                 */
                case '/dialog':
                    $data = $this->botManagerOne($array[1], $msg->userid);
                    if ($data) {
                        $list = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread', 'u.silence'])
                            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
                            ->where('web_socket_dialogs.name', 'LIKE', "%{$array[2]}%")
                            ->where('u.userid', $data->userid)
                            ->orderByDesc('u.top_at')
                            ->orderByDesc('web_socket_dialogs.last_at')
                            ->take(20)
                            ->get();
                        if ($list->isEmpty()) {
                            $type = "notice";
                            $notice = "没有搜索到相关会话。";
                        } else {
                            $list->transform(function (WebSocketDialog $item) use ($data) {
                                return $item->formatData($data->userid);
                            });
                            $data->list = $list;
                        }
                    } else {
                        $type = "notice";
                        $notice = "机器人不存在。";
                    }
                    break;
            }
            //
            $text = view('push.bot', [
                'type' => $type,
                'data' => $data,
                'notice' => $notice,
                'version' => Base::getVersion()
            ])->render();
            $text = preg_replace("/^\x20+/", "", $text);
            $text = preg_replace("/\n\x20+/", "\n", $text);
            WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'text', ['text' => $text], $this->userid, false, false, true);    // todo 未能在任务end事件来发送任务
        }
    }

    /**
     * @param $botId
     * @param $userid
     * @return User
     */
    private function botManagerOne($botId, $userid)
    {
        $botId = intval($botId);
        $userid = intval($userid);
        if ($botId > 0) {
            return User::select(['users.*'])
                ->join('user_bots', 'users.userid', '=', 'user_bots.bot_id')
                ->where('users.bot', 1)
                ->where('user_bots.bot_id', $botId)
                ->where('user_bots.userid', $userid)
                ->first();
        }
        return null;
    }
}
