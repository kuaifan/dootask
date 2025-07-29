<?php

namespace App\Tasks;

use App\Models\FileContent;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Report;
use App\Models\User;
use App\Models\UserBot;
use App\Models\UserDepartment;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogConfig;
use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Ihttp;
use App\Module\TextExtractor;
use Carbon\Carbon;
use Exception;
use DB;

/**
 * 机器人消息接收处理任务
 * 
 * @package App\Tasks
 */
class BotReceiveMsgTask extends AbstractTask
{
    protected $userid;          // 机器人ID
    protected $msgId;           // 消息ID
    protected $mention;         // 是否提及（机器人被@，不含@所有人）
    protected $mentionOther;    // 是否提及其他人
    protected $client = [];     // 客户端信息（版本、语言、平台）

    /**
     * 构造函数
     * 初始化机器人消息处理任务的相关参数
     * 
     * @param int $userid 机器人用户ID
     * @param int $msgId 消息ID
     * @param array $mentions 提及的用户ID数组
     * @param array $client 客户端信息（版本、语言、平台等）
     */
    public function __construct($userid, $msgId, $mentions, $client = [])
    {
        parent::__construct(...func_get_args());
        $this->userid = $userid;
        $this->msgId = $msgId;
        $this->mention = array_intersect([$userid], $mentions) ? 1 : 0;
        $this->mentionOther = array_diff($mentions, [0, $userid]) ? 1 : 0;
        $this->client = is_array($client) ? $client : [];
    }

    /**
     * 任务开始执行
     * 验证机器人用户和消息的有效性，然后处理机器人接收到的消息
     */
    public function start()
    {
        // 判断是否是机器人用户
        $botUser = User::whereUserid($this->userid)->whereBot(1)->first();
        if (empty($botUser)) {
            return;
        }

        // 判断消息是否存在
        $msg = WebSocketDialogMsg::with(['user'])->find($this->msgId);
        if (empty($msg)) {
            return;
        }

        // 标记消息已读
        $msg->readSuccess($botUser->userid);

        // 判断消息是否是机器人发送的则不处理，避免循环
        if (!$msg->user || $msg->user->bot) {
            return;
        }

        // 处理机器人消息
        $this->processMessage($msg, $botUser);
    }

    /**
     * 任务结束回调
     * 当前为空实现，可在此处添加清理逻辑
     */
    public function end()
    {

    }

    /**
     * 处理机器人接收到的消息
     * 根据消息类型和机器人类型执行相应的处理逻辑
     * 
     * @param WebSocketDialogMsg $msg 接收到的消息对象
     * @param User $botUser 机器人用户对象
     * @return void
     */
    private function processMessage(WebSocketDialogMsg $msg, User $botUser)
    {
        // 位置消息（仅支持签到机器人）
        if ($msg->type === 'location') {
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

        // 提取指令
        $sendText = $this->extractMessageContent($msg);
        $replyText = null;
        if ($msg->reply_id && $replyMsg = WebSocketDialogMsg::find($msg->reply_id)) {
            $replyText = $this->extractMessageContent($replyMsg);
        }

        // 没有提取到指令，则不处理
        if (empty($sendText) && empty($replyText)) {
            return;
        }

        // 查询会话
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }

        // 如果是群聊，@别人但是没有@自己，则不处理
        if ($dialog->type === 'group' && $this->mentionOther && !$this->mention) {
            return;
        }

        // 推送Webhook
        $this->handleWebhookRequest($sendText, $replyText, $msg, $dialog, $botUser);

        // 如果不是用户对话，则只处理到这里
        if ($dialog->type !== 'user') {
            return;
        }

        // 签到机器人
        if ($botUser->email === 'check-in@bot.system') {
            $content = UserBot::checkinBotQuickMsg($sendText, $msg->userid);
            if ($content) {
                WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                    'type' => 'content',
                    'content' => $content,
                ], $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
            }
        }

        // 隐私机器人
        if ($botUser->email === 'anon-msg@bot.system') {
            $array = UserBot::anonBotQuickMsg($sendText);
            if ($array) {
                WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                    'type' => 'content',
                    'title' => $array['title'],
                    'content' => $array['content'],
                ], $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
            }
        }

        // 管理机器人
        if (str_starts_with($sendText, '/')) {
            // 判断是否是机器人管理员
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

            // 指令处理
            $array = Base::newTrim(explode(" ", "{$sendText}    "));
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
                    $data = $this->getBotInfo($botId, $msg->userid);
                    if (!$data) {
                        $content = "机器人不存在。";
                    }
                    break;

                /**
                 * 创建
                 */
                case '/newbot':
                    $res = UserBot::newBot($msg->userid, $array[1]);
                    if (Base::isError($res)) {
                        $content = $res['msg'];
                    } else {
                        $data = $res['data'];
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                    $data = $this->getBotInfo($botId, $msg->userid);
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
                            ->map(function ($item) use ($data) {
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

            // 回复消息
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
                    $msgData['email'] = $botUser->email;
                    $msgData['version'] = Base::getVersion();
                } elseif ($type == '/help') {
                    $msgData['manager'] = $isManager;
                }
            }
            WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', $msgData, $botUser->userid, false, false, true);    // todo 未能在任务end事件来发送任务
        }
    }

    /**
     * 处理机器人Webhook请求
     * 根据机器人类型（AI机器人或用户机器人）发送相应的Webhook请求
     * 
     * @param string $sendText 发送的文本内容
     * @param string $replyText 回复的文本内容
     * @param WebSocketDialogMsg $msg 消息对象
     * @param WebSocketDialog $dialog 对话对象
     * @param User $botUser 机器人用户对象
     * @return void
     */
    private function handleWebhookRequest($sendText, $replyText, WebSocketDialogMsg $msg, WebSocketDialog $dialog, User $botUser)
    {
        $webhookUrl = null;
        $extras = ['timestamp' => time()];

        try {
            if ($botUser->isAiBot($type)) {
                // AI机器人
                if (Base::val($msg->msg, 'forward_data.leave')) {
                    // AI机器人不处理带有留言的转发消息，因为他要处理那条留言消息
                    return;
                }
                if (in_array($this->client['platform'], ['win', 'mac', 'web']) && !Base::judgeClientVersion("0.41.11", $this->client['version'])) {
                    throw new Exception('当前客户端版本低（所需版本≥v0.41.11）。');
                }
                if (!Apps::isInstalled('ai')) {
                    throw new Exception('应用「AI Robot」未安装');
                }
                // 整理机器人参数
                $setting = Base::setting('aibotSetting');
                $extras = [
                    'model_type' => match ($type) {
                        'qianwen' => 'qwen',
                        default => $type,
                    },
                    'model_name' => $setting[$type . '_model'],
                    'system_message' => $setting[$type . '_system'],
                    'api_key' => $setting[$type . '_key'],
                    'base_url' => $setting[$type . '_base_url'],
                    'agency' => $setting[$type . '_agency'],
                    'server_url' => 'http://nginx',
                ];
                if ($setting[$type . '_temperature']) {
                    $extras['temperature'] = floatval($setting[$type . '_temperature']);
                }
                if ($msg->msg['model_name']) {
                    $extras['model_name'] = $msg->msg['model_name'];
                }
                // 提取模型“思考”参数
                $thinkPatterns = [
                    "/^(.+?)(\s+|\s*[_-]\s*)(think|thinking|reasoning)\s*$/",
                    "/^(.+?)\s*\(\s*(think|thinking|reasoning)\s*\)\s*$/"
                ];
                $thinkMatch = [];
                foreach ($thinkPatterns as $pattern) {
                    if (preg_match($pattern, $extras['model_name'], $thinkMatch)) {
                        break;
                    }
                }
                if ($thinkMatch && !empty($thinkMatch[1])) {
                    $extras['model_name'] = $thinkMatch[1];
                    $extras['max_tokens'] = 20000;
                    $extras['thinking'] = 4096;
                    $extras['temperature'] = 1.0;
                }
                // 设定会话ID
                if ($dialog->session_id) {
                    $extras['context_key'] = 'session_' . $dialog->session_id;
                }
                // 设置文心一言的API密钥
                if ($type === 'wenxin') {
                    $extras['api_key'] .= ':' . $setting['wenxin_secret'];
                }
                if ($type === 'ollama') {
                    if (empty($extras['base_url'])) {
                        throw new Exception('机器人未启用。');
                    }
                    if (empty($extras['api_key'])) {
                        $extras['api_key'] = Base::strRandom(6);
                    }
                }
                if (empty($extras['api_key'])) {
                    throw new Exception('机器人未启用。');
                }
                $this->generateSystemPromptForAI($msg->userid, $dialog, $extras);
                // 转换提及格式
                $sendText = $this->convertMentionForAI($sendText);
                $replyText = $this->convertMentionForAI($replyText);
                if ($replyText) {
                    $sendText = <<<EOF
                        <quoted_content>
                        {$replyText}
                        </quoted_content>

                        The content within the above quoted_content tags is a citation.

                        {$sendText}
                        EOF;
                }
                $webhookUrl = "http://nginx/ai/chat";
            } else {
                // 用户机器人
                if ($botUser->isUserBot() && str_starts_with($sendText, '/')) {
                    // 用户机器人不处理指令类型命令
                    return;
                }
                $userBot = UserBot::whereBotId($botUser->userid)->first();
                if ($userBot) {
                    $userBot->webhook_num++;
                    $userBot->save();
                    $webhookUrl = $userBot->webhook_url;
                }
            }
            if (!preg_match("/^https?:\/\//", $webhookUrl)) {
                return;
            }
        } catch (\Exception $e) {
            WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                'type' => 'content',
                'content' => $e->getMessage(),
            ], $botUser->userid, false, false, true); // todo 未能在任务end事件来发送任务
            return;
        }
        //
        try {
            $data = [
                'text' => $sendText,
                'reply_text' => $replyText,
                'token' => User::generateToken($botUser),
                'session_id' => $dialog->session_id,
                'dialog_id' => $dialog->id,
                'dialog_type' => $dialog->type,
                'msg_id' => $msg->id,
                'msg_uid' => $msg->userid,
                'mention' => $this->mention ? 1 : 0,
                'bot_uid' => $botUser->userid,
                'version' => Base::getVersion(),
                'extras' => Base::array2json($extras)
            ];
            // 添加用户信息
            $userInfo = User::find($msg->userid);
            if ($userInfo) {
                $data['msg_user'] = [
                    'userid' => $userInfo->userid,
                    'email' => $userInfo->email,
                    'nickname' => $userInfo->nickname,
                    'profession' => $userInfo->profession,
                    'lang' => $userInfo->lang,
                    'token' => User::generateTokenNoDevice($userInfo, now()->addHour()),
                ];
            }
            // 请求Webhook
            $result = Ihttp::ihttp_post($webhookUrl, $data, 30);
            if ($result['data'] && $data = Base::json2array($result['data'])) {
                if ($data['code'] != 200 && $data['message']) {
                    WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'text', [
                        'text' => $result['data']['message']
                    ], $botUser->userid, false, false, true);
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
     * 提取消息内容
     * 根据消息类型（文件、文本等）提取相应的内容文本
     * 
     * @param WebSocketDialogMsg $msg 消息对象
     * @return string 提取出的消息文本内容
     */
    private function extractMessageContent(WebSocketDialogMsg $msg)
    {
        switch ($msg->type) {
            case "file":
                // 提取文件消息
                $msgData = Base::json2array($msg->getRawOriginal('msg'));
                return $this->convertMentionFormat("path", $msgData['path'], $msgData['name']);

            case "text":
                // 提取文本消息
                $original = $msg->msg['text'] ?: '';
                if (empty($original)) {
                    return '';
                }

                // 提取快捷键
                if (preg_match("/<span[^>]*?data-quick-key=([\"'])([^\"']+?)\\1[^>]*?>(.*?)<\/span>/is", $original, $match)) {
                    $command = $match[2] ?? '';
                    $command = preg_replace("/^%3A\.?/", ":", $command);
                    $command = trim($command);
                    if ($command) {
                        return $command;
                    }
                }

                // 提及任务、文件、报告
                $original = preg_replace_callback_array([
                    // 用户
                    "/<span class=\"mention user\" data-id=\"(\d+)\">(.*?)<\/span>/" => function () {
                        return "";
                    },

                    // 任务
                    "/<span class=\"mention task\" data-id=\"(\d+)\">(.*?)<\/span>/" => function ($match) {
                        return $this->convertMentionFormat("task", $match[1], $match[2]);
                    },

                    // 文件
                    "/<a class=\"mention file\" href=\"([^\"']+?)\"[^>]*?>(.*?)<\/a>/" => function ($match) {
                        if (preg_match("/single\/file\/(.*?)$/", $match[1], $subMatch)) {
                            return $this->convertMentionFormat("file", $subMatch[1], $match[2]);
                        }
                        return "";
                    },

                    // 报告
                    "/<a class=\"mention report\" href=\"([^\"']+?)\"[^>]*?>(.*?)<\/a>/" => function ($match) {
                        if (preg_match("/single\/report\/detail\/(.*?)$/", $match[1], $subMatch)) {
                            return $this->convertMentionFormat("report", $subMatch[1], $match[2]);
                        }
                        return "";
                    },
                ], $original);

                // 转成 markdown 并返回
                return Base::html2markdown($original);

            default:
                // 其他类型消息不处理
                return '';
        }
    }

    /**
     * 转换提及消息格式
     * 将提及的任务、文件、报告等转换为统一的格式 [type#key#name]
     * 
     * @param string $type 提及类型（task、file、report、path）
     * @param string $key 提及对象的唯一标识
     * @param string $name 提及对象的显示名称
     * @return string 格式化后的提及字符串
     */
    private function convertMentionFormat($type, $key, $name)
    {
        $key = preg_replace('/[#\[\]]/', '', $key);
        $name = preg_replace('/[#\[\]]/', '', $name);
        return "[{$type}#{$key}#{$name}]";
    }

    /**
     * 为AI机器人转换提及消息格式
     * 将提及的任务、文件、报告转换为AI可理解的格式，并提取相关内容
     * 
     * @param string $original 原始消息文本
     * @return string 转换后的消息文本，包含相关内容的标签
     * @throws Exception 当提及的对象不存在或读取失败时抛出异常
     */
    private function convertMentionForAI($original)
    {
        $array = [];
        $original = preg_replace_callback('/\[([^#\[\]]+)#([^#\[\]]+)#([^#\[\]]*)\]/', function ($match) use (&$array) {
            // 初始化 tag 内容
            $pathTag = null;
            $pathName = null;
            $pathContent = null;

            // 根据 type 提取 tag 内容
            switch ($match[1]) {
                // 任务
                case 'task':
                    $taskInfo = ProjectTask::with(['content'])->whereId(intval($match[2]))->first();
                    if (!$taskInfo) {
                        throw new Exception("任务不存在或已被删除");
                    }
                    $pathTag = "task_content";
                    $pathName = addslashes($taskInfo->name) . " (ID:{$taskInfo->id})";
                    $pathContent = implode("\n", $taskInfo->AIContext());
                    break;

                // 文件
                case 'file':
                    $fileInfo = FileContent::idOrCodeToContent($match[2]);
                    if (!$fileInfo || !isset($fileInfo->content['url'])) {
                        throw new Exception("文件不存在或已被删除");
                    }
                    $urlPath = public_path($fileInfo->content['url']);
                    if (!file_exists($urlPath)) {
                        throw new Exception("文件不存在或已被删除");
                    }
                    $fileResult = TextExtractor::extractFile($urlPath);
                    if (Base::isError($fileResult)) {
                        throw new Exception("文件读取失败：" . $fileResult['msg']);
                    }
                    $pathTag = "file_content";
                    $pathName = addslashes($match[3]) . " (ID:{$fileInfo->id})";
                    $pathContent = $fileResult['data'];
                    break;

                // 文件路径
                case 'path':
                    $urlPath = public_path($match[2]);
                    if (!file_exists($urlPath)) {
                        throw new Exception("文件不存在或已被删除");
                    }
                    $fileResult = TextExtractor::extractFile($urlPath);
                    if (Base::isError($fileResult)) {
                        throw new Exception("文件读取失败：" . $fileResult['msg']);
                    }
                    $pathTag = "file_content";
                    $pathName = addslashes($match[3]);
                    $pathContent = $fileResult['data'];
                    break;

                // 报告
                case 'report':
                    $reportInfo = Report::idOrCodeToContent($match[2]);
                    if (!$reportInfo) {
                        throw new Exception("报告不存在或已被删除");
                    }
                    $pathTag = "report_content";
                    $pathName = addslashes($match[3]) . " (ID:{$reportInfo->id})";
                    $pathContent = $reportInfo->content;
                    break;
            }

            // 如果提取到 tag 内容，则添加到 contents 数组中
            if ($pathTag) {
                $array[] = "<{$pathTag} path=\"{$pathName}\">\n{$pathContent}\n</{$pathTag}>";
                return "`{$pathName}` (see below for {$pathTag} tag)";
            }

            return "";
        }, $original);

        // 添加 tag 内容
        if ($array) {
            $original .= "\n\n" . implode("\n\n", $array);
        }

        return $original;
    }

    /**
     * 为AI机器人生成系统提示词
     * 根据对话类型（用户对话、项目群、任务群、部门群等）生成相应的系统提示词
     * 
     * @param int|null $userid 用户ID
     * @param WebSocketDialog $dialog 对话对象
     * @param array $extras 额外参数数组，通过引用传递以修改system_message
     * @return void
     */
    private function generateSystemPromptForAI($userid, WebSocketDialog $dialog, array &$extras)
    {
        $system_messages = [];
        switch ($dialog->type) {
            // 用户对话
            case "user":
                $aiPrompt = WebSocketDialogConfig::where([
                    'dialog_id' => $dialog->id,
                    'userid' => $userid,
                    'type' => 'ai_prompt',
                ])->value('value');
                if ($aiPrompt) {
                    $extras['system_message'] = $aiPrompt;
                }
                break;
            // 群组对话
            case "group":
                switch ($dialog->group_type) {
                    // 用户群
                    case 'user':
                        break;
                    // 项目群
                    case 'project':
                        $projectInfo = Project::whereDialogId($dialog->id)->first();
                        if ($projectInfo) {
                            $projectDesc = $projectInfo->desc ?: "-";
                            $projectStatus = $projectInfo->archived_at ? '已归档' : '正在进行中';
                            $system_messages[] = <<<EOF
                                当前我在项目【{$projectInfo->name}】中
                                项目描述：{$projectDesc}
                                项目状态：{$projectStatus}

                                如果你判断我想要或需要添加任务，请按照以下格式回复：

                                ::: create-task-list
                                title: 任务标题1
                                desc: 任务描述1

                                title: 任务标题2
                                desc: 任务描述2
                                :::
                                EOF;
                        }
                        break;
                    // 任务群
                    case 'task':
                        $taskInfo = ProjectTask::with(['content'])->whereDialogId($dialog->id)->first();
                        if ($taskInfo) {
                            $taskContext = implode("\n", $taskInfo->AIContext());
                            $system_messages[] = <<<EOF
                                当前我在任务【{$taskInfo->name}】中
                                当前时间：{$taskInfo->updated_at}
                                任务ID：{$taskInfo->id}
                                {$taskContext}

                                如果你判断我想要或需要添加子任务，请按照以下格式回复：

                                ::: create-subtask-list
                                title: 子任务标题1
                                title: 子任务标题2
                                :::
                                EOF;
                        }
                        break;
                    // 部门群
                    case 'department':
                        $userDepartment = UserDepartment::whereDialogId($dialog->id)->first();
                        if ($userDepartment) {
                            $system_messages[] = "当前我在【{$userDepartment->name}】的部门群聊中";
                        }
                        break;
                    // 全体成员群
                    case 'all':
                        $system_messages[] = "当前我在【全体成员】的群聊中";
                        break;
                }
                break;
        }
        if ($extras['system_message']) {
            array_unshift($system_messages, $extras['system_message']);
        }
        if ($system_messages) {
            $extras['system_message'] = implode("\n\n----------------\n\n", Base::newTrim($system_messages));
        }
    }

    /**
     * 获取机器人信息
     * 根据机器人ID和用户ID获取机器人的详细信息，包括清理设置和webhook配置
     * 
     * @param int $botId 机器人ID
     * @param int $userid 用户ID
     * @return User|null 机器人用户对象，如果不存在则返回null
     */
    private function getBotInfo($botId, $userid)
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
