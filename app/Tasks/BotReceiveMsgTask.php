<?php

namespace App\Tasks;

use App\Models\User;
use App\Models\UserBot;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Ihttp;
use Carbon\Carbon;
use DB;

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
    protected $mention;
    protected $client = [];

    public function __construct($userid, $msgId, $mention, $client = [])
    {
        parent::__construct(...func_get_args());
        $this->userid = $userid;
        $this->msgId = $msgId;
        $this->mention = $mention;
        $this->client = is_array($client) ? $client : [];
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
        $this->botManagerReceive($msg, $botUser);
    }

    public function end()
    {

    }

    /**
     * 机器人管理处理消息
     * @param WebSocketDialogMsg $msg
     * @param User $botUser
     * @return void
     */
    private function botManagerReceive(WebSocketDialogMsg $msg, User $botUser)
    {
        // 位置消息
        if ($msg->type === 'location') {
            // 签到机器人
            if ($botUser->email === 'check-in@bot.system') {
                $content = UserBot::checkinBotQuickMsg('locat-checkin', $msg->userid, $msg->msg);
                if ($content) {
                    WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                        'type' => 'content',
                        'content' => $content,
                    ], $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
                }
            }
            return;
        }

        // 文本消息
        if ($msg->type !== 'text') {
            return;
        }
        $original = $msg->msg['text'];
        if ($this->mention) {
            $original = preg_replace("/<span class=\"mention user\" data-id=\"(\d+)\">(.*?)<\/span>/", "", $original);
        }
        if (preg_match("/<span[^>]*?data-quick-key=([\"'])(.*?)\\1[^>]*?>(.*?)<\/span>/is", $original, $match)) {
            $command = $match[2];
            if (str_starts_with($command, '%3A.')) {
                $command = ":" . substr($command, 4);
            }
        } else {
            $command = trim(strip_tags($original));
        }
        //
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }
        // 推送Webhook
        if ($command
            && !str_starts_with($command, '/')
            && ($dialog->type === 'user' || $this->mention)) {
            $this->botManagerWebhook($command, $msg, $botUser, $dialog);
        }
        if ($dialog->type !== 'user') {
            return;
        }
        // 签到机器人
        if ($botUser->email === 'check-in@bot.system') {
            $content = UserBot::checkinBotQuickMsg($command, $msg->userid);
            if ($content) {
                WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                    'type' => 'content',
                    'content' => $content,
                ], $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
            }
        }
        // 隐私机器人
        if ($botUser->email === 'anon-msg@bot.system') {
            $array = UserBot::anonBotQuickMsg($command);
            if ($array) {
                WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                    'type' => 'content',
                    'title' => $array['title'],
                    'content' => $array['content'],
                ], $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
            }
        }
        // 管理机器人
        if (str_starts_with($command, '/')) {
            if ($botUser->email === 'bot-manager@bot.system') {
                $isManager = true;
            } elseif (UserBot::whereBotId($botUser->userid)->whereUserid($msg->userid)->exists()) {
                $isManager = false;
            } else {
                WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                    'type' => 'content',
                    'content' => "非常抱歉，我不是你的机器人，无法完成你的指令。",
                ], $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
                return;
            }
            //
            $array = Base::newTrim(explode(" ", "{$command}    "));
            $type = $array[0];
            $data = [];
            $content = "";
            if (!$isManager && in_array($type, ['/list', '/newbot'])) {
                return; // 这些操作仅支持【机器人管理】机器人
            }
            switch ($type) {
                /**
                 * 列表
                 */
                case '/list':
                    $data = User::select([
                        'users.*',
                        'user_bots.clear_day',
                        'user_bots.clear_at',
                        'user_bots.webhook_url',
                        'user_bots.webhook_num'
                    ])
                        ->join('user_bots', 'users.userid', '=', 'user_bots.bot_id')
                        ->where('users.bot', 1)
                        ->where('user_bots.userid', $msg->userid)
                        ->take(50)
                        ->orderByDesc('id')
                        ->get();
                    if ($data->isEmpty()) {
                        $content = "您没有创建机器人。";
                    }
                    break;

                /**
                 * 详情
                 */
                case '/hello':
                case '/info':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if (!$data) {
                        $content = "机器人不存在。";
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
                        $content = "超过最大创建数量。";
                        break;
                    }
                    if (strlen($array[1]) < 2 || strlen($array[1]) > 20) {
                        $content = "机器人名称由2-20个字符组成。";
                        break;
                    }
                    $data = User::botGetOrCreate("user-" . Base::generatePassword(), [
                        'nickname' => $array[1]
                    ], $msg->userid);
                    if (empty($data)) {
                        $content = "创建失败。";
                        break;
                    }
                    $dialog = WebSocketDialog::checkUserDialog($data, $msg->userid);
                    if ($dialog) {
                        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                            'type' => '/hello',
                            'title' => '创建成功。',
                            'data' => $data,
                        ], $data->userid);    // todo 未能在任务end事件来发送任务
                    }
                    break;

                /**
                 * 修改名字
                 */
                case '/setname':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $nameString = $isManager ? $array[2] : $array[1];
                    if (strlen($nameString) < 2 || strlen($nameString) > 20) {
                        $content = "机器人名称由2-20个字符组成。";
                        break;
                    }
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if ($data) {
                        $data->nickname = $nameString;
                        $data->az = Base::getFirstCharter($nameString);
                        $data->pinyin = Base::cn2pinyin($nameString);
                        $data->save();
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;


                /**
                 * 删除
                 */
                case '/deletebot':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if ($data) {
                        $data->deleteUser('delete bot');
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;

                /**
                 * 获取Token
                 */
                case '/token':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if ($data) {
                        User::generateToken($data);
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;

                /**
                 * 更新Token
                 */
                case '/revoke':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if ($data) {
                        $data->encrypt = Base::generatePassword(6);
                        $data->password = Doo::md5s(Base::generatePassword(32), $data->encrypt);
                        $data->save();
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;

                /**
                 * 设置保留消息时间
                 */
                case '/clearday':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $clearDay = $isManager ? $array[2] : $array[1];
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if ($data) {
                        $userBot = UserBot::whereBotId($botId)->whereUserid($msg->userid)->first();
                        if ($userBot) {
                            $userBot->clear_day = min(intval($clearDay) ?: 30, 999);
                            $userBot->clear_at = Carbon::now()->addDays($userBot->clear_day);
                            $userBot->save();
                        }
                        $data->clear_day = $userBot->clear_day;
                        $data->clear_at = $userBot->clear_at;   // 这两个参数只是作为输出，所以不保存
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;

                /**
                 * 设置webhook
                 */
                case '/webhook':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $webhookUrl = $isManager ? $array[2] : $array[1];
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if (strlen($webhookUrl) > 255) {
                        $content = "webhook地址最长仅支持255个字符。";
                    } elseif ($data) {
                        $userBot = UserBot::whereBotId($botId)->whereUserid($msg->userid)->first();
                        if ($userBot) {
                            $userBot->webhook_url = $webhookUrl ?: "";
                            $userBot->webhook_num = 0;
                            $userBot->save();
                        }
                        $data->webhook_url = $userBot->webhook_url ?: '-';
                        $data->webhook_num = $userBot->webhook_num;   // 这两个参数只是作为输出，所以不保存
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;

                /**
                 * 会话搜索
                 */
                case '/dialog':
                    $botId = $isManager ? $array[1] : $botUser->userid;
                    $nameKey = $isManager ? $array[2] : $array[1];
                    $data = $this->botManagerOne($botId, $msg->userid);
                    if ($data) {
                        $list = DB::table('web_socket_dialog_users as u')
                            ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
                            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
                            ->where('u.userid', $data->userid)
                            ->where('d.name', 'LIKE', "%{$nameKey}%")
                            ->whereNull('d.deleted_at')
                            ->orderByDesc('u.top_at')
                            ->orderByDesc('u.last_at')
                            ->take(20)
                            ->get()
                            ->map(function($item) use ($data) {
                                return WebSocketDialog::synthesizeData($item, $data->userid);
                            })
                            ->all();
                        if (empty($list)) {
                            $content = "没有搜索到相关会话。";
                        } else {
                            $data->list = $list;   // 这个参数只是作为输出，所以不保存
                        }
                    } else {
                        $content = "机器人不存在。";
                    }
                    break;
            }
            //

            if ($content) {
                $msgData = [
                    'type' => 'content',
                    'content' => $content,
                ];
            } else {
                $msgData = [
                    'type' => $type,
                    'data' => $data,
                ];
                $msgData['title'] = match ($type) {
                    '/hello' => '您好',
                    '/help' => '帮助指令',
                    '/list' => '我的机器人',
                    '/info' => '机器人信息',
                    '/newbot' => '新建机器人',
                    '/setname' => '设置名称',
                    '/deletebot' => '删除机器人',
                    '/token' => '机器人Token',
                    '/revoke' => '更新Token',
                    '/webhook' => '设置Webhook',
                    '/clearday' => '设置保留消息时间',
                    '/dialog' => '对话列表',
                    '/api' => 'API接口文档',
                    default => '不支持的指令',
                };
                if ($type == '/api') {
                    $msgData['version'] = Base::getVersion();
                } elseif ($type == '/help') {
                    $msgData['manager'] = $isManager;
                }
            }
            WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', $msgData, $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
        }
    }

    /**
     * 机器人处理 Webhook
     * @param string $command
     * @param WebSocketDialogMsg $msg
     * @param User $botUser
     * @param WebSocketDialog $dialog
     * @return void
     */
    private function botManagerWebhook(string $command, WebSocketDialogMsg $msg, User $botUser, WebSocketDialog $dialog)
    {
        $serverUrl = 'http://' . env('APP_IPPR') . '.3';
        $userBot = null;
        $extras = [];
        $errorContent = null;
        switch ($botUser->email) {
            // ChatGPT 机器人
            case 'ai-openai@bot.system':
                $setting = Base::setting('aibotSetting');
                $webhookUrl = "{$serverUrl}/ai/openai/send";
                $extras = [
                    'openai_key' => $setting['openai_key'],
                    'openai_agency' => $setting['openai_agency'],
                    'openai_model' => $setting['openai_model'],
                    'server_url' => $serverUrl,
                    'chunk_size' => 7,
                ];
                if (empty($extras['openai_key'])) {
                    $errorContent = '机器人未启用。';
                } elseif (in_array($this->client['platform'], ['win', 'mac', 'web'])
                    && !Base::judgeClientVersion("0.29.11", $this->client['version'])) {
                    $errorContent = '当前客户端版本低（所需版本≥v0.29.11）。';
                }
                break;
            // Claude 机器人
            case 'ai-claude@bot.system':
                $setting = Base::setting('aibotSetting');
                $webhookUrl = "{$serverUrl}/ai/claude/send";
                $extras = [
                    'claude_token' => $setting['claude_token'],
                    'claude_agency' => $setting['claude_agency'],
                    'server_url' => $serverUrl,
                ];
                if (empty($extras['claude_token'])) {
                    $errorContent = '机器人未启用。';
                } elseif (in_array($this->client['platform'], ['win', 'mac', 'web'])
                    && !Base::judgeClientVersion("0.29.11", $this->client['version'])) {
                    $errorContent = '当前客户端版本低（所需版本≥v0.29.11）。';
                }
                break;
            // Wenxin 机器人
            case 'ai-wenxin@bot.system':
                $setting = Base::setting('aibotSetting');
                $webhookUrl = "{$serverUrl}/ai/wenxin/send";
                $extras = [
                    'wenxin_key' => $setting['wenxin_key'],
                    'wenxin_secret' => $setting['wenxin_secret'],
                    'wenxin_model' => $setting['wenxin_model'],
                    'server_url' => $serverUrl,
                ];
                if (empty($extras['wenxin_key'])) {
                    $errorContent = '机器人未启用。';
                } elseif (in_array($this->client['platform'], ['win', 'mac', 'web'])
                    && !Base::judgeClientVersion("0.29.11", $this->client['version'])) {
                    $errorContent = '当前客户端版本低（所需版本≥v0.29.12）。';
                }
                break;
            // QianWen 机器人
            case 'ai-qianwen@bot.system':
                $setting = Base::setting('aibotSetting');
                $webhookUrl = "{$serverUrl}/ai/qianwen/send";
                $extras = [
                    'qianwen_key' => $setting['qianwen_key'],
                    'qianwen_model' => $setting['qianwen_model'],
                    'server_url' => $serverUrl,
                ];
                if (empty($extras['qianwen_key'])) {
                    $errorContent = '机器人未启用。';
                } elseif (in_array($this->client['platform'], ['win', 'mac', 'web'])
                    && !Base::judgeClientVersion("0.29.11", $this->client['version'])) {
                    $errorContent = '当前客户端版本低（所需版本≥v0.29.12）。';
                }
                break;
            // Gemini 机器人
            case 'ai-gemini@bot.system':
                $setting = Base::setting('aibotSetting');
                $webhookUrl = "{$serverUrl}/ai/gemini/send";
                $extras = [
                    'gemini_key' => $setting['gemini_key'],
                    'gemini_model' => $setting['gemini_model'],
                    'gemini_agency' => $setting['gemini_agency'],
                    'gemini_timeout' => 20,
                    'server_url' => $serverUrl,
                ];
                if (empty($extras['gemini_key'])) {
                    $errorContent = '机器人未启用。';
                } elseif (in_array($this->client['platform'], ['win', 'mac', 'web'])
                    && !Base::judgeClientVersion("0.29.11", $this->client['version'])) {
                    $errorContent = '当前客户端版本低（所需版本≥v0.29.12）。';
                }
                break;
            // 智谱清言 机器人
            case 'ai-zhipu@bot.system':
                $setting = Base::setting('aibotSetting');
                $webhookUrl = "{$serverUrl}/ai/zhipu/send";
                $extras = [
                    'zhipu_key' => $setting['zhipu_key'],
                    'zhipu_model' => $setting['zhipu_model'],
                    'server_url' => $serverUrl,
                ];
                if (empty($extras['zhipu_key'])) {
                    $errorContent = '机器人未启用。';
                } elseif (in_array($this->client['platform'], ['win', 'mac', 'web'])
                    && !Base::judgeClientVersion("0.29.11", $this->client['version'])) {
                    $errorContent = '当前客户端版本低（所需版本≥v0.29.12）。';
                }
                break;
            // 其他机器人
            default:
                $userBot = UserBot::whereBotId($botUser->userid)->first();
                $webhookUrl = $userBot?->webhook_url;
                break;
        }
        if ($errorContent) {
            WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                'type' => 'content',
                'content' => $errorContent,
            ], $botUser->userid, false, false, true); // todo 未能在任务end事件来发送任务
            return;
        }
        if (!preg_match("/^https*:\/\//", $webhookUrl)) {
            return;
        }
        //
        try {
            $data = [
                'text' => $command,
                'token' => User::generateToken($botUser),
                'dialog_id' => $dialog->id,
                'dialog_type' => $dialog->type,
                'msg_id' => $msg->id,
                'msg_uid' => $msg->userid,
                'mention' => $this->mention ? 1 : 0,
                'bot_uid' => $botUser->userid,
                'version' => Base::getVersion(),
                'extras' => Base::array2json($extras)
            ];
            $res = Ihttp::ihttp_post($webhookUrl, $data);
            if ($userBot) {
                $userBot->webhook_num++;
                $userBot->save();
            }
            if ($res['data'] && $data = Base::json2array($res['data'])) {
                if ($data['code'] != 200 && $data['message']) {
                    WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'text', ['text' => $res['data']['message']], $botUser->userid, false, false, true);
                }
            }
        } catch (\Throwable $th) {
            info(Base::array2json([
                'bot_userid' => $botUser->userid,
                'dialog' => $dialog->id,
                'msg' => $msg->id,
                'webhook_url' => $webhookUrl,
                'error' => $th->getMessage(),
            ]));
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
            return User::select([
                'users.*',
                'user_bots.clear_day',
                'user_bots.clear_at',
                'user_bots.webhook_url',
                'user_bots.webhook_num'
            ])
                ->join('user_bots', 'users.userid', '=', 'user_bots.bot_id')
                ->where('users.bot', 1)
                ->where('user_bots.bot_id', $botId)
                ->where('user_bots.userid', $userid)
                ->first();
        }
        return null;
    }
}
