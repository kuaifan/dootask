<?php

namespace App\Models;

use App\Module\Base;
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
     * 机器人菜单
     * @param $email
     * @return array|array[]
     */
    public static function quickMsgs($email)
    {
        if ($email === 'check-in@bot.system') {
            return [
                [
                    'key' => 'checkin',
                    'label' => Base::Lang('我要签到')
                ], [
                    'key' => 'it',
                    'label' => Base::Lang('IT资讯')
                ], [
                    'key' => '36ke',
                    'label' => Base::Lang('36氪')
                ], [
                    'key' => '60s',
                    'label' => Base::Lang('60s读世界')
                ], [
                    'key' => 'joke',
                    'label' => Base::Lang('一个笑话')
                ], [
                    'key' => 'soup',
                    'label' => Base::Lang('一碗鸡汤')
                ]
            ];
        }
        return [];
    }

    /**
     * 签到机器人
     * @param $type
     * @param $userid
     * @return string
     */
    public static function checkinBotQuickMsg($type, $userid)
    {
        if (Cache::get("UserBot::checkinBotQuickMsg:{$userid}") === "yes") {
            return "操作频繁！";
        }
        Cache::put("UserBot::checkinBotQuickMsg:{$userid}", "yes", Carbon::now()->addSecond());
        //
        switch ($type) {
            case "checkin":
                $text = "暂未开放手动签到。";
                break;

            default:
                $text = Extranet::checkinBotQuickMsg($type);
                break;
        }
        return $text ?: '维护中...';
    }
}
