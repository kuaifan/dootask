<?php


namespace App\Models;

use App\Module\Base;

/**
 * App\Models\UserCheckinRecord
 *
 * @property int $id
 * @property int|null $userid 会员id
 * @property string|null $mac MAC地址
 * @property int|null $time 上报的时间戳
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereMac($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereUserid($value)
 * @mixin \Eloquent
 */
class UserCheckinRecord extends AbstractModel
{

}
