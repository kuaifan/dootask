<?php

namespace App\Models;

/**
 * App\Models\NotifyRule
 *
 * @property int $id
 * @property string|null $mode 类型
 * @property string|null $name 名称
 * @property string|null $event 触发条件
 * @property string|null $content 发送内容
 * @property string|null $webhook_url webhook地址
 * @property float|null $expire_hours 时间条件
 * @property int|null $status 是否启用
 * @property int|null $total 累计通知次数
 * @property string|null $last_at 最后通知时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereExpireHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyRule whereWebhookUrl($value)
 * @mixin \Eloquent
 */
class NotifyRule extends AbstractModel
{

}
