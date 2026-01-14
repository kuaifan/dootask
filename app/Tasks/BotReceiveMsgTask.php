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
use App\Module\PromptPlaceholder;
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
        $sendText = $msg->extractMessageContent();
        $replyText = null;
        if ($msg->reply_id && $replyMsg = WebSocketDialogMsg::find($msg->reply_id)) {
            $replyText = $replyMsg->extractMessageContent();
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
        $userBot = null;
        $extras = ['timestamp' => time()];

        try {
            if ($botUser->isAiBot($type)) {
                // AI机器人，不处理带有留言的转发消息，因为他要处理那条留言消息
                if (Base::val($msg->msg, 'forward_data.leave')) {
                    return;
                }
                // 如果是群聊，没有@自己，则不处理
                if ($dialog->type === 'group' && !$this->mention) {
                    return;
                }
                // 检查客户端版本
                if (in_array($this->client['platform'], ['win', 'mac', 'web']) && !Base::judgeClientVersion("0.41.11", $this->client['version'])) {
                    throw new Exception('当前客户端版本低（所需版本≥v0.41.11）。');
                }
                // 判断AI应用是否安装
                if (!Apps::isInstalled('ai')) {
                    throw new Exception('应用「AI Assistant」未安装');
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
                // 群聊清理上下文（群聊不使用上下文）
                if ($dialog->type === 'group') {
                    $extras['before_clear'] = 1;
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
                $this->generateSystemPromptForAI($msg->userid, $dialog, $botUser, $extras);
                // 转换提及格式
                if ($replyText) {
                    $sendText = <<<EOF
                        <quoted_content>
                        {$replyText}
                        </quoted_content>

                        上述 quoted_content 标签中的内容为引用。

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
                if (!$userBot || !$userBot->shouldDispatchWebhook(UserBot::WEBHOOK_EVENT_MESSAGE)) {
                    return;
                }
            }
        } catch (\Exception $e) {
            WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'template', [
                'type' => 'content',
                'content' => $e->getMessage(),
            ], $botUser->userid, false, false, true); // todo 未能在任务end事件来发送任务
            return;
        }

        // 基本请求数据
        $data = [
            'event' => UserBot::WEBHOOK_EVENT_MESSAGE,
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
            'extras' => Base::array2json($extras),
            'version' => Base::getVersion(),
            'timestamp' => time(),
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

        $result = null;
        if ($userBot) {
            $result = $userBot->dispatchWebhook(UserBot::WEBHOOK_EVENT_MESSAGE, $data);
        } else {
            try {
                $result = Ihttp::ihttp_post($webhookUrl, $data, 30);
            } catch (\Throwable $th) {
                info(Base::array2json([
                    'webhook_url' => $webhookUrl,
                    'data' => $data,
                    'error' => $th->getMessage(),
                ]));
            }
        }

        if ($result && isset($result['data'])) {
            $responseData = Base::json2array($result['data']);
            if (($responseData['code'] ?? 0) === 200 && !empty($responseData['message'])) {
                WebSocketDialogMsg::sendMsg(null, $msg->dialog_id, 'text', [
                    'text' => $responseData['message']
                ], $botUser->userid, false, false, true);
            }
        }
    }

    /**
     * 为AI机器人生成系统提示词
     * 根据对话类型（用户对话、项目群、任务群、部门群等）生成相应的系统提示词
     *
     * @param int|null $userid 用户ID
     * @param WebSocketDialog $dialog 对话对象
     * @param User $botUser 机器人用户对象
     * @param array $extras 额外参数数组，通过引用传递以修改system_message
     * @return void
     */
    private function generateSystemPromptForAI($userid, WebSocketDialog $dialog, User $botUser, array &$extras)
    {
        // 用户自定义提示词（私聊场景优先使用）
        $customPrompt = null;
        if ($dialog->type === 'user') {
            $customPrompt = WebSocketDialogConfig::where([
                'dialog_id' => $dialog->id,
                'userid' => $userid,
                'type' => 'ai_prompt',
            ])->value('value');
        }

        $prompt = [];

        // 1. 基础角色（自定义提示词优先）
        if ($customPrompt) {
            $prompt[] = $customPrompt;
        } elseif (!empty($extras['system_message'])) {
            $prompt[] = $extras['system_message'];
        }

        // 2. 上下文信息
        $currentTime = Carbon::now()->toDateTimeString();
        $contextLines = [
            "您是：{$botUser->nickname}（ID: {$botUser->userid}）",
            "当前对话ID(dialog_id)：{$dialog->id}",
            "当前系统时间(now)：{$currentTime}",
        ];

        if ($dialog->type === 'group') {
            switch ($dialog->group_type) {
                case 'project':
                    $projectInfo = Project::whereDialogId($dialog->id)->first();
                    if ($projectInfo) {
                        $contextLines[] = "场景：项目群聊「{$projectInfo->name}」（project_id: {$projectInfo->id}）";
                    }
                    break;

                case 'task':
                    $taskInfo = ProjectTask::with(['content'])->whereDialogId($dialog->id)->first();
                    if ($taskInfo) {
                        $contextLines[] = "场景：任务群聊「{$taskInfo->name}」（task_id: {$taskInfo->id}）";
                    }
                    break;

                case 'department':
                    $userDepartment = UserDepartment::whereDialogId($dialog->id)->first();
                    if ($userDepartment) {
                        $contextLines[] = "场景：部门群聊「{$userDepartment->name}」";
                    }
                    break;

                case 'all':
                    $contextLines[] = "场景：全体成员群聊";
                    break;
            }

            // 3. 聊天历史（仅群聊）
            $chatHistory = $this->getRecentChatHistory($dialog, 15);
            if ($chatHistory) {
                $prompt[] = implode("\n", $contextLines);
                $prompt[] = "最近的对话记录：\n{$chatHistory}";
            } else {
                $prompt[] = implode("\n", $contextLines);
            }
        } else {
            $prompt[] = implode("\n", $contextLines);
        }

        // 4. 条件性提示块（用户上下文 + 格式指南）
        $prompt[] = PromptPlaceholder::buildOptionalPrompts($userid, $dialog);

        $extras['system_message'] = implode("\n----\n", array_filter($prompt));
    }

    /**
     * 获取最近的聊天记录
     * @param WebSocketDialog $dialog 对话对象
     * @param int $limit 获取的聊天记录条数
     * @return string|null 格式化后的聊天记录字符串，无记录时返回null
     */
    private function getRecentChatHistory(WebSocketDialog $dialog, $limit = 10): ?string
    {
        // 构建查询条件
        $conditions = [
            ['dialog_id', '=', $dialog->id],
            ['id', '<', $this->msgId],
        ];

        // 如果有会话ID，添加会话过滤条件
        if ($dialog->session_id > 0) {
            $conditions[] = ['session_id', '=', $dialog->session_id];
        }

        // 查询最近$limit条消息并格式化
        $chatMessages = WebSocketDialogMsg::with(['user'])
            ->where($conditions)
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->map(function (WebSocketDialogMsg $message) {
                $userName = $message->user?->nickname ?? '未知用户';
                $content = $message->extractMessageContent(500);
                if (!$content) {
                    return null;
                }
                // 使用XML标签格式，确保AI能清晰识别边界
                // 对用户名进行HTML转义，防止特殊字符破坏格式
                $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
                return "<message userid=\"{$message->userid}\" nickname=\"{$safeUserName}\">\n{$content}\n</message>";
            })
            ->reverse() // 反转集合，让时间顺序正确（最早的在前）
            ->filter() // 过滤掉空内容的消息
            ->values() // 重新索引数组
            ->toArray();

        return empty($chatMessages) ? null : implode("\n", $chatMessages);
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
