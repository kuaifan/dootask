<?php

namespace App\Models;

/**
 * App\Models\Meeting
 *
 * @property int $id
 * @property string|null $meetingid 会议ID，不是数字
 * @property string|null $name 会议主题
 * @property string|null $channel 频道
 * @property int|null $userid 创建人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $end_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereMeetingid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereUserid($value)
 * @mixin \Eloquent
 */
class Meeting extends AbstractModel
{

}
