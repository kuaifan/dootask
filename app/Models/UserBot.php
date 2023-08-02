<?php

namespace App\Models;

use App\Module\Doo;
use App\Module\Extranet;
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
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot query()
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
            'ai-wenxin' => 'Wenxin',
            'bot-manager' => '机器人管理',
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
                    'label' => Doo::translate('我要签到')
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
        $text = match ($command) {
            "checkin" => "暂未开放手动签到。",
            default => Extranet::checkinBotQuickMsg($command),
        };
        return $text ?: '维护中...';
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
