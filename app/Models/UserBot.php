<?php

namespace App\Models;

use App\Module\Base;
use App\Module\Doo;
use App\Module\Extranet;
use App\Tasks\JokeSoupTask;
use Cache;
use Carbon\Carbon;

/**
 * App\Models\UserBot
 *
 * @property int $id
 * @property int|null $userid 所属人ID
 * @property int|null $bot_id 机器人ID
 * @property int|null $clear_day 消息自动清理天数
 * @property string|null $clear_at 下一次清理时间
 * @property string|null $webhook_url 消息webhook地址
 * @property int|null $webhook_num 消息webhook请求次数
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
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereWebhookNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereWebhookUrl($value)
 * @mixin \Eloquent
 */
class UserBot extends AbstractModel
{

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
        return match ($name) {
            'system-msg' => '系统消息',
            'task-alert' => '任务提醒',
            'check-in' => '签到打卡',
            'anon-msg' => '匿名消息',
            'approval-alert' => '审批',
            'ai-openai' => 'ChatGPT',
            'ai-claude' => 'Claude',
            'ai-wenxin' => '文心一言',
            'ai-qianwen' => '通义千问',
            'ai-gemini' => 'Gemini',
            'ai-zhipu' => '智谱清言',
            'bot-manager' => '机器人管理',
            'meeting-alert' => '会议通知',
            'okr-alert' => 'OKR提醒',
            default => '',  // 不是系统机器人时返回空（也可以拿来判断是否是系统机器人）
        };
    }

    /**
     * 机器人菜单
     * @param $email
     * @return array|array[]
     */
    public static function quickMsgs($email)
    {
        return match ($email) {
            'check-in@bot.system' => [
                [
                    'key' => 'checkin',
                    'label' => Doo::translate('我要打卡')
                ], [
                    'key' => 'it',
                    'label' => Doo::translate('IT资讯')
                ], [
                    'key' => '36ke',
                    'label' => Doo::translate('36氪')
                ], [
                    'key' => '60s',
                    'label' => Doo::translate('60s读世界')
                ], [
                    'key' => 'joke',
                    'label' => Doo::translate('开心笑话')
                ], [
                    'key' => 'soup',
                    'label' => Doo::translate('心灵鸡汤')
                ]
            ],
            'anon-msg@bot.system' => [
                [
                    'key' => 'help',
                    'label' => Doo::translate('使用说明')
                ], [
                    'key' => 'privacy',
                    'label' => Doo::translate('隐私说明')
                ],
            ],
            'bot-manager@bot.system' => [
                [
                    'key' => '/help',
                    'label' => Doo::translate('帮助指令')
                ], [
                    'key' => '/api',
                    'label' => Doo::translate('Api接口文档')
                ], [
                    'key' => '/list',
                    'label' => Doo::translate('我的机器人')
                ],
            ],
            'ai-openai@bot.system',
            'ai-claude@bot.system',
            'ai-wenxin@bot.system',
            'ai-gemini@bot.system',
            'ai-zhipu@bot.system',
            'ai-qianwen@bot.system' => [
                [
                    'key' => '%3A.clear',
                    'label' => Doo::translate('清空上下文')
                ]
            ],
            default => [],
        };

    }

    /**
     * 签到机器人
     * @param $command
     * @param $userid
     * @return string
     */
    public static function checkinBotQuickMsg($command, $userid)
    {
        if (Cache::get("UserBot::checkinBotQuickMsg:{$userid}") === "yes") {
            return "操作频繁！";
        }
        Cache::put("UserBot::checkinBotQuickMsg:{$userid}", "yes", Carbon::now()->addSecond());
        //
        if ($command === 'checkin') {
            $setting = Base::setting('checkinSetting');
            if ($setting['open'] !== 'open') {
                return '暂未开启签到功能。';
            }
            if (!in_array('manual', $setting['modes'])) {
                return '暂未开放手动签到。';
            }
            if ($error = UserBot::checkinBotCheckin($userid, Base::time(), true)) {
                return $error;
            }
            return null;
        } else {
            return Extranet::checkinBotQuickMsg($command);
        }
    }

    /**
     * 签到机器人签到
     * @param $mac
     * @param $time
     * @param bool $alreadyTip  签到过是否提示
     * @return string|null 返回string表示错误信息，返回null表示签到成功
     */
    public static function checkinBotCheckin($mac, $time, $alreadyTip = false)
    {
        $setting = Base::setting('checkinSetting');
        $times = $setting['time'] ? Base::json2array($setting['time']) : ['09:00', '18:00'];
        $advance = (intval($setting['advance']) ?: 120) * 60;
        $delay = (intval($setting['delay']) ?: 120) * 60;
        //
        $nowDate = date("Y-m-d");
        $nowTime = date("H:i:s");
        //
        $timeStart = strtotime("{$nowDate} {$times[0]}");
        $timeEnd = strtotime("{$nowDate} {$times[1]}");
        $timeAdvance = max($timeStart - $advance, strtotime($nowDate));
        $timeDelay = min($timeEnd + $delay, strtotime("{$nowDate} 23:59:59"));
        if (Base::time() < $timeAdvance || $timeDelay < Base::time()) {
            return "不在有效时间内，有效时间为：" . date("H:i", $timeAdvance) . "-" . date("H:i", $timeDelay);
        }
        //
        $macs = explode(",", $mac);
        $checkins = [];
        foreach ($macs as $mac) {
            $mac = strtoupper($mac);
            $array = [];
            if (Base::isMac($mac)) {
                if ($UserCheckinMac = UserCheckinMac::whereMac($mac)->first()) {
                    $array = [
                        'userid' => $UserCheckinMac->userid,
                        'mac' => $UserCheckinMac->mac,
                        'date' => $nowDate,
                    ];
                    $checkins[] = [
                        'userid' => $UserCheckinMac->userid,
                        'remark' => $UserCheckinMac->remark,
                    ];
                }
            } elseif (Base::isNumber($mac)) {
                if ($UserInfo = User::whereUserid($mac)->whereBot(0)->first()) {
                    $array = [
                        'userid' => $UserInfo->userid,
                        'mac' => '00:00:00:00:00:00',
                        'date' => $nowDate,
                    ];
                    $checkins[] = [
                        'userid' => $UserInfo->userid,
                        'remark' => '手动签到',
                    ];
                }
            }
            if ($array) {
                $record = UserCheckinRecord::where($array)->first();
                if (empty($record)) {
                    $record = UserCheckinRecord::createInstance($array);
                }
                $record->times = Base::array2json(array_merge($record->times, [$nowTime]));
                $record->report_time = $time;
                $record->save();
            }
        }
        //
        if ($checkins && $botUser = User::botGetOrCreate('check-in')) {
            $getJokeSoup = function($type) {
                $pre = $type == "up" ? "每日开心：" : "心灵鸡汤：";
                $key = $type == "up" ? "jokes" : "soups";
                $array = Base::json2array(Cache::get(JokeSoupTask::keyName($key)));
                if ($array) {
                    $item = $array[array_rand($array)];
                    if ($item) {
                        return $pre . $item;
                    }
                }
                return null;
            };
            $sendMsg = function($type, $checkin) use ($alreadyTip, $getJokeSoup, $botUser, $nowDate) {
                $cacheKey = "Checkin::sendMsg-{$nowDate}-{$type}:" . $checkin['userid'];
                $typeDesc = $type == "up" ? "上班" : "下班";
                if (Cache::get($cacheKey) === "yes") {
                    if ($alreadyTip && $dialog = WebSocketDialog::checkUserDialog($botUser, $checkin['userid'])) {
                        $text = "<p>今日已{$typeDesc}打卡，无需重复打卡。</p>";
                        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, $type != "up");
                    }
                    return;
                }
                Cache::put($cacheKey, "yes", Carbon::now()->addDay());
                //
                if ($dialog = WebSocketDialog::checkUserDialog($botUser, $checkin['userid'])) {
                    $hi = date("H:i");
                    $remark = $checkin['remark'] ? " ({$checkin['remark']})": "";
                    $text = "<p>{$typeDesc}打卡成功，打卡时间: {$hi}{$remark}</p>";
                    $suff = $getJokeSoup($type);
                    if ($suff) {
                        $text = "{$text}<p>----------</p><p>{$suff}</p>";
                    }
                    WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, $type != "up");
                }
            };
            if ($timeAdvance <= Base::time() && Base::time() < $timeEnd) {
                // 上班打卡通知（从最早打卡时间 到 下班打卡时间）
                foreach ($checkins as $checkin) {
                    $sendMsg('up', $checkin);
                }
            }
            if ($timeEnd <= Base::time() && Base::time() <= $timeDelay) {
                // 下班打卡通知（下班打卡时间 到 最晚打卡时间）
                foreach ($checkins as $checkin) {
                    $sendMsg('down', $checkin);
                }
            }
        }
        return null;
    }

    /**
     * 隐私机器人
     * @param $command
     * @return string
     */
    public static function anonBotQuickMsg($command)
    {
        return match ($command) {
            "help" => "使用说明：打开你想要发匿名消息的个人对话，点击输入框右边的 ⊕ 号，选择 <u>匿名消息</u> 即可输入你想要发送的匿名消息内容。",
            "privacy" => "匿名消息将通过 <u>匿名消息（机器人）</u> 发送给对方，不会记录你的身份信息。",
            default => '',
        };
    }
}
