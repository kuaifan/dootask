<?php


namespace App\Models;

use App\Module\Base;

/**
 * App\Models\UserCheckin
 *
 * @property int $id
 * @property int|null $userid 会员id
 * @property string|null $mac MAC地址
 * @property string|null $remark 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin whereMac($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckin whereUserid($value)
 * @mixin \Eloquent
 */
class UserCheckin extends AbstractModel
{

}
