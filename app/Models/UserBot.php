<?php

namespace App\Models;

use App\Module\Base;
use App\Module\Doo;
use App\Module\Ihttp;
use App\Module\Timer;
use App\Tasks\JokeSoupTask;
use Cache;
use Carbon\Carbon;
use Throwable;

/**
 * App\Models\UserBot
 *
 * @property int $id
 * @property int|null $userid 所属人ID
 * @property int|null $bot_id 机器人ID
 * @property int|null $clear_day 消息自动清理天数
 * @property \Illuminate\Support\Carbon|null $clear_at 下一次清理时间
 * @property string|null $webhook_url 消息webhook地址
 * @property int|null $webhook_num 消息webhook请求次数
 * @property array $webhook_events Webhook事件配置
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereBotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereClearAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereClearDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereWebhookEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereWebhookNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereWebhookUrl($value)
 * @mixin \Eloquent
 */
class UserBot extends AbstractModel
{
    public const WEBHOOK_EVENT_MESSAGE = 'message';
    public const WEBHOOK_EVENT_DIALOG_OPEN = 'dialog_open';
    public const WEBHOOK_EVENT_MEMBER_JOIN = 'member_join';
    public const WEBHOOK_EVENT_MEMBER_LEAVE = 'member_leave';

    protected $casts = [
        'webhook_events' => 'array',
    ];

    /**
     * 获取 webhook 事件配置
     *
     * @param mixed $value
     * @return array
     */
    public function getWebhookEventsAttribute(mixed $value): array
    {
        if ($value === null || $value === '') {
            return self::normalizeWebhookEvents(null, true);
        }
        return self::normalizeWebhookEvents($value, false);
    }

    /**
     * 设置 webhook 事件配置
     *
     * @param mixed $value
     * @return void
     */
    public function setWebhookEventsAttribute(mixed $value): void
    {
        $useFallback = $value === null;
        $this->attributes['webhook_events'] = Base::array2json(self::normalizeWebhookEvents($value, $useFallback));
    }

    /**
     * 判断是否需要触发指定 webhook 事件
     *
     * @param string $event
     * @return bool
     */
    public function shouldDispatchWebhook(string $event): bool
    {
        if (!$this->webhook_url) {
            return false;
        }
        if (!preg_match('/^https?:\/\//', $this->webhook_url)) {
            return false;
        }
        return in_array($event, $this->webhook_events ?? [], true);
    }

    /**
     * 发送 webhook
     *
     * @param string $event
     * @param array $data
     * @param int $timeout
     * @return array|null
     */
    public function dispatchWebhook(string $event, array $data, int $timeout = 30): ?array
    {
        if (!$this->shouldDispatchWebhook($event)) {
            return null;
        }

        try {
            $data['event'] = $event;
            $result = Ihttp::ihttp_post($this->webhook_url, $data, $timeout);
            $this->increment('webhook_num');
            return $result;
        } catch (Throwable $th) {
            info(Base::array2json([
                'webhook_url' => $this->webhook_url,
                'data' => $data,
                'error' => $th->getMessage(),
            ]));
            return null;
        }
    }

    /**
     * 判断是否系统机器人
     * @param $email
     * @return bool
     */
    public static function isSystemBot($email)
    {
        return str_ends_with($email, '@bot.system') && self::systemBotName($email);
    }

    /**
     * 系统机器人名称
     * @param $name string 邮箱 或 邮箱前缀
     * @return string
     */
    public static function systemBotName($name)
    {
        if (str_contains($name, "@")) {
            $name = explode("@", $name)[0];
        }
        $name = match ($name) {
            'system-msg' => '系统消息',
            'task-alert' => '任务提醒',
            'check-in' => '签到打卡',
            'anon-msg' => '匿名消息',
            'approval-alert' => '审批',
            'ai-openai' => 'ChatGPT',
            'ai-claude' => 'Claude',
            'ai-deepseek' => 'DeepSeek',
            'ai-gemini' => 'Gemini',
            'ai-grok' => 'Grok',
            'ai-ollama' => 'Ollama',
            'ai-zhipu' => '智谱清言',
            'ai-qianwen' => '通义千问',
            'ai-wenxin' => '文心一言',
            'bot-manager' => '机器人管理',
            'meeting-alert' => '会议通知',
            'okr-alert' => 'OKR提醒',
            default => '',  // 不是系统机器人时返回空（也可以拿来判断是否是系统机器人）
        };
        return Doo::translate($name);
    }

    /**
     * 机器人菜单
     * @param $email
     * @return array|array[]
     */
    public static function quickMsgs($email)
    {
        switch ($email) {
            case 'check-in@bot.system':
                $menu = [];
                $setting = Base::setting('checkinSetting');
                if ($setting['open'] !== 'open') {
                    return $menu;
                }
                if (in_array('locat', $setting['modes']) && Base::isEEUIApp()) {
                    $mapTypes = [
                        'baidu' => ['key' => 'locat_bd_lbs_key', 'point' => 'locat_bd_lbs_point', 'msg' => '请填写百度地图AK'],
                        'amap' => ['key' => 'locat_amap_key', 'point' => 'locat_amap_point', 'msg' => '请填写高德地图Key'],
                        'tencent' => ['key' => 'locat_tencent_key', 'point' => 'locat_tencent_point', 'msg' => '请填写腾讯地图Key'],
                    ];
                    $type = $setting['locat_map_type'];
                    if (isset($mapTypes[$type])) {
                        $conf = $mapTypes[$type];
                        $point = $setting[$conf['point']];
                        $menu[] = [
                            'key' => 'locat-checkin',
                            'label' => Doo::translate('定位签到'),
                            'config' => [
                                'type' => $type,
                                'key' => $setting[$conf['key']],
                                'lng' => $point['lng'],
                                'lat' => $point['lat'],
                                'radius' => intval($point['radius']),
                            ]
                        ];
                    }
                }
                if (in_array('manual', $setting['modes'])) {
                    $menu[] = [
                        'key' => 'manual-checkin',
                        'label' => Doo::translate('手动签到')
                    ];
                }
                return $menu;

            case 'anon-msg@bot.system':
                return [
                    [
                        'key' => 'help',
                        'label' => Doo::translate('使用说明')
                    ], [
                        'key' => 'privacy',
                        'label' => Doo::translate('隐私说明')
                    ],
                ];

            case 'meeting-alert@bot.system':
                if (!Base::judgeClientVersion('0.39.89')) {
                    return [];
                }
                return [
                    [
                        'key' => 'meeting-create',
                        'label' => Doo::translate('新会议')
                    ],
                    [
                        'key' => 'meeting-join',
                        'label' => Doo::translate('加入会议')
                    ],
                ];

            case 'bot-manager@bot.system':
                return [
                    [
                        'key' => '/help',
                        'label' => Doo::translate('帮助指令')
                    ], [
                        'key' => '/api',
                        'label' => Doo::translate('API接口文档')
                    ], [
                        'key' => '/list',
                        'label' => Doo::translate('我的机器人')
                    ],
                ];

            default:
                if (preg_match('/^(ai-|user-session-)(.*?)@bot\.system$/', $email, $match)) {
                    $menus = [
                        [
                            'key' => '~ai-session-create',
                            'label' => Doo::translate('开启新会话'),
                        ],
                        [
                            'key' => '~ai-session-history',
                            'label' => Doo::translate('历史会话'),
                        ]
                    ];
                    if ($match[1] === "ai-") {
                        $aibotSetting = Base::setting('aibotSetting');
                        $aibotModel = $aibotSetting[$match[2] . '_model'];
                        $aibotModels = Setting::AIBotModels2Array($aibotSetting[$match[2] . '_models']);
                        if ($aibotModels) {
                            $menus = array_merge(
                                [
                                    [
                                        'key' => '~ai-model-select',
                                        'label' => Doo::translate('选择模型'),
                                        'config' => [
                                            'model' => $aibotModel,
                                            'models' => $aibotModels
                                        ]
                                    ]
                                ],
                                $menus
                            );
                        }
                    }
                    return $menus;
                }
                return [];
        }
    }

    /**
     * 签到机器人
     * @param $command
     * @param $userid
     * @param $extra
     * @return string
     */
    public static function checkinBotQuickMsg($command, $userid, $extra = [])
    {
        if (Cache::get("UserBot::checkinBotQuickMsg:{$userid}") === "yes") {
            return "操作频繁！";
        }
        Cache::put("UserBot::checkinBotQuickMsg:{$userid}", "yes", Carbon::now()->addSecond());
        //
        if ($command === 'manual-checkin') {
            $setting = Base::setting('checkinSetting');
            if ($setting['open'] !== 'open') {
                return '暂未开启签到功能。';
            }
            if (!in_array('manual', $setting['modes'])) {
                return '暂未开放手动签到。';
            }
            UserBot::checkinBotCheckin('manual-' . $userid, Timer::time(), true);
        } elseif ($command === 'locat-checkin') {
            $setting = Base::setting('checkinSetting');
            if ($setting['open'] !== 'open') {
                return '暂未开启签到功能。';
            }
            if (!in_array('locat', $setting['modes'])) {
                return '暂未开放定位签到。';
            }
            if (empty($extra)) {
                return '当前客户端版本低（所需版本≥v0.39.75）。';
            }
            if (in_array($extra['type'], ['baidu', 'amap', 'tencent'])) {
                // todo 判断距离
            } else {
                return '错误的定位签到。';
            }
            UserBot::checkinBotCheckin('locat-' . $userid, Timer::time(), true);
        }
        return null;
    }

    /**
     * 签到机器人签到
     * @param mixed $mac
     * - 多个使用,分隔
     * - 支持：mac地址、(manual|locat|face|checkin)-userid
     * @param $time
     * @param bool $alreadyTip  签到过是否提示
     */
    public static function checkinBotCheckin($mac, $time, $alreadyTip = false)
    {
        $setting = Base::setting('checkinSetting');
        $times = $setting['time'] ? Base::json2array($setting['time']) : ['09:00', '18:00'];
        $advance = (intval($setting['advance']) ?: 120) * 60;
        $delay = (intval($setting['delay']) ?: 120) * 60;
        //
        $currentTime = Timer::time();
        $nowDate = date("Y-m-d");
        $nowTime = date("H:i:s");
        $yesterdayDate = date("Y-m-d", strtotime("-1 day"));
        //
        // 今天的签到窗口
        $timeStart = strtotime("{$nowDate} {$times[0]}");
        $timeEnd = strtotime("{$nowDate} {$times[1]}");
        $timeAdvance = max($timeStart - $advance, strtotime($nowDate));
        // 移除 23:59:59 限制，允许跨天
        $todayTimeDelay = $timeEnd + $delay;
        //
        // 昨天的延后窗口（用于判断凌晨打卡归属）
        $yesterdayTimeEnd = strtotime("{$yesterdayDate} {$times[1]}");
        $yesterdayTimeDelay = $yesterdayTimeEnd + $delay;
        //
        // 判断签到归属哪天
        $targetDate = null;
        $checkType = null; // 'up' 或 'down'
        //
        // 情况1：在今天的有效窗口内
        if ($currentTime >= $timeAdvance && $currentTime <= $todayTimeDelay) {
            $targetDate = $nowDate;
            if ($currentTime < $timeEnd) {
                $checkType = 'up';
            } else {
                $checkType = 'down';
            }
        }
        // 情况2：凌晨时段，检查是否在昨天的延后窗口内
        elseif ($currentTime < $timeAdvance && $currentTime <= $yesterdayTimeDelay) {
            $targetDate = $yesterdayDate;
            $checkType = 'down';
        }
        //
        // 构建错误消息
        $errorTime = false;
        if (!$targetDate) {
            $displayDelay = date("H:i", $todayTimeDelay % 86400);
            $nextDay = ($todayTimeDelay > strtotime("{$nowDate} 23:59:59")) ? "(+1)" : "";
            $errorTime = "不在有效时间内，有效时间为：" . date("H:i", $timeAdvance) . "-{$displayDelay}{$nextDay}";
        }
        //
        $macs = explode(",", $mac);
        $checkins = [];
        $array = [];
        foreach ($macs as $mac) {
            $mac = strtoupper($mac);
            if (Base::isMac($mac)) {
                // 路由器签到
                if ($UserCheckinMac = UserCheckinMac::whereMac($mac)->first()) {
                    $array[] = [
                        'userid' => $UserCheckinMac->userid,
                        'mac' => $UserCheckinMac->mac,
                        'date' => $targetDate ?: $nowDate,
                    ];
                    $checkins[] = [
                        'userid' => $UserCheckinMac->userid,
                        'remark' => $UserCheckinMac->remark,
                    ];
                }
            } elseif (preg_match('/^(manual|locat|face|checkin)-(\d+)$/i', $mac, $match)) {
                // 机器签到、手动签到、定位签到
                $type = str_replace('checkin', 'face', strtolower($match[1]));
                $mac = intval($match[2]);
                $remark = match ($type) {
                    'manual' => $setting['manual_remark'] ?: 'Manual',
                    'locat' => $setting['locat_remark'] ?: 'Location',
                    'face' => $setting['face_remark'] ?: 'Machine',
                    default => '',
                };
                if ($UserInfo = User::whereUserid($mac)->whereBot(0)->first()) {
                    $array[] = [
                        'userid' => $UserInfo->userid,
                        'mac' => '00:00:00:00:00:00',
                        'date' => $targetDate ?: $nowDate,
                    ];
                    $checkins[] = [
                        'userid' => $UserInfo->userid,
                        'remark' => $remark,
                    ];
                }
            }
        }
        if (!$errorTime) {
            foreach ($array as $item) {
                $record = UserCheckinRecord::where($item)->first();
                if (empty($record)) {
                    $record = UserCheckinRecord::createInstance($item);
                }
                $record->times = Base::array2json(array_merge($record->times, [$nowTime]));
                $record->report_time = $time;
                $record->save();
            }
        }
        //
        if ($checkins && $botUser = User::botGetOrCreate('check-in')) {
            $getJokeSoup = function($type, $userid) {
                $pre = $type == "up" ? "每日开心：" : "心灵鸡汤：";
                $key = $type == "up" ? "jokes" : "soups";
                $array = Base::json2array(Cache::get(JokeSoupTask::keyName($key)));
                if ($array) {
                    $item = $array[array_rand($array)];
                    if ($item) {
                        Doo::setLanguage($userid);
                        return Doo::translate($pre . $item);
                    }
                }
                return null;
            };
            $sendMsg = function($type, $checkin) use ($errorTime, $alreadyTip, $getJokeSoup, $botUser, $targetDate, $nowDate) {
                $displayDate = $targetDate ?: $nowDate;
                $dialog = WebSocketDialog::checkUserDialog($botUser, $checkin['userid']);
                if (!$dialog) {
                    return;
                }
                // 判断错误
                if ($errorTime) {
                    if ($alreadyTip) {
                        $text = $errorTime;
                        $text .= $checkin['remark'] ? " ({$checkin['remark']})": "";
                        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                            'type' => 'content',
                            'content' => $text,
                        ], $botUser->userid, false, false, true);
                    }
                    return;
                }
                // 判断已打卡（使用目标日期作为缓存键）
                $cacheKey = "Checkin::sendMsg-{$displayDate}-{$type}:" . $checkin['userid'];
                $typeContent = $type == "up" ? "上班" : "下班";
                if (Cache::get($cacheKey) === "yes") {
                    if ($alreadyTip) {
                        $dateHint = ($displayDate != $nowDate) ? "({$displayDate}) " : "今日";
                        $text = "{$dateHint}已{$typeContent}打卡，无需重复打卡。";
                        $text .= $checkin['remark'] ? " ({$checkin['remark']})": "";
                        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                            'type' => 'content',
                            'content' => $text,
                        ], $botUser->userid, false, false, true);
                    }
                    return;
                }
                Cache::put($cacheKey, "yes", Carbon::now()->addDay());
                // 打卡成功
                $hi = date("H:i");
                $remark = $checkin['remark'] ? " ({$checkin['remark']})": "";
                $subcontent = $getJokeSoup($type, $checkin['userid']);
                $dateInfo = ($displayDate != $nowDate) ? " ({$displayDate})" : "";
                $title = "{$typeContent}打卡成功，打卡时间: {$hi}{$remark}{$dateInfo}";
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $title,
                    'content' => [
                        [
                            'content' => $title
                        ], [
                            'content' => $subcontent,
                            'language' => false,
                            'style' => 'padding-top:4px;opacity:0.6',
                        ]
                    ],
                ], $botUser->userid, false, false, $type != "up");
            };
            // 根据打卡类型发送通知
            if ($checkType === 'up') {
                foreach ($checkins as $checkin) {
                    $sendMsg('up', $checkin);
                }
            }
            if ($checkType === 'down') {
                foreach ($checkins as $checkin) {
                    $sendMsg('down', $checkin);
                }
            }
        }
    }

    /**
     * 隐私机器人
     * @param $command
     * @return array
     */
    public static function anonBotQuickMsg($command)
    {
        return match ($command) {
            "help" => [
                "title" => "匿名消息使用说明",
                "content" => "使用说明：打开你想要发匿名消息的个人对话，点击输入框右边的 ⊕ 号，选择「匿名消息」即可输入你想要发送的匿名消息内容。"
            ],
            "privacy" => [
                "title" => "匿名消息隐私说明",
                "content" => "匿名消息将通过「匿名消息（机器人）」发送给对方，不会记录你的身份信息。"
            ],
            default => [],
        };
    }

    /**
     * 创建我的机器人
     * @param int $userid               创建人userid
     * @param string $botName           机器人名称
     * @param bool $sessionSupported    是否支持会话
     * @return array
     */
    public static function newBot($userid, $botName, $sessionSupported = false)
    {
        if (User::select(['users.*'])
                ->join('user_bots', 'users.userid', '=', 'user_bots.bot_id')
                ->where('users.bot', 1)
                ->where('user_bots.userid', $userid)
                ->count() >= 50) {
            return Base::retError("超过最大创建数量。");
        }
        if (strlen($botName) < 2 || strlen($botName) > 20) {
            return Base::retError("机器人名称由2-20个字符组成。");
        }
        $botType = ($sessionSupported ? "user-session-" : "user-normal-") . Base::generatePassword();
        $data = User::botGetOrCreate($botType, [
            'nickname' => $botName
        ], $userid);
        if (empty($data)) {
            return Base::retError("创建失败。");
        }
        $dialog = WebSocketDialog::checkUserDialog($data, $userid);
        if ($dialog) {
            if ($sessionSupported) {
                $dialogSession = WebSocketDialogSession::create([
                    'dialog_id' => $dialog->id,
                ]);
                $dialogSession->save();
                $dialog->session_id = $dialogSession->id;
                $dialog->save();
            }
            WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                'type' => '/hello',
                'title' => '创建成功。',
                'data' => $data,
            ], $data->userid);
        }
        return Base::retSuccess("创建成功。", $data);
    }

    /**
     * 获取可选的 webhook 事件
     *
     * @return string[]
     */
    public static function webhookEventOptions(): array
    {
        return [
            self::WEBHOOK_EVENT_MESSAGE,
            self::WEBHOOK_EVENT_DIALOG_OPEN,
            self::WEBHOOK_EVENT_MEMBER_JOIN,
            self::WEBHOOK_EVENT_MEMBER_LEAVE,
        ];
    }

    /**
     * 标准化 webhook 事件配置
     *
     * @param mixed $events
     * @param bool $useFallback
     * @return array
     */
    public static function normalizeWebhookEvents(mixed $events, bool $useFallback = true): array
    {
        if (is_string($events)) {
            $events = Base::json2array($events);
        }
        if ($events === null) {
            $events = [];
        }
        if (!is_array($events)) {
            $events = [$events];
        }
        $events = array_filter(array_map('strval', $events));
        $events = array_values(array_intersect($events, self::webhookEventOptions()));
        return $events ?: ($useFallback ? [self::WEBHOOK_EVENT_MESSAGE] : []);
    }
}
