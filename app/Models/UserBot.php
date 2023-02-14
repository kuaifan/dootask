<?php

namespace App\Models;

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

}
