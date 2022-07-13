<?php


namespace App\Models;


/**
 * App\Models\UserDelete
 *
 * @property int $id
 * @property int|null $userid 用户id
 * @property string|null $email 邮箱帐号
 * @property string|null $reason 注销原因
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereUserid($value)
 * @mixin \Eloquent
 */
class UserDelete extends AbstractModel
{

}
