<?php


namespace App\Models;

use App\Module\Base;

/**
 * App\Models\UserDelete
 *
 * @property int $id
 * @property int|null $operator 操作人员
 * @property int|null $userid 用户id
 * @property string|null $email 邮箱帐号
 * @property string|null $reason 注销原因
 * @property string $cache 会员资料缓存
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereCache($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete whereUserid($value)
 * @mixin \Eloquent
 */
class UserDelete extends AbstractModel
{
    /**
     * 昵称
     * @param $value
     * @return string
     */
    public function getCacheAttribute($value)
    {
        if (!is_array($value)) {
            $value = Base::json2array($value);
            // 昵称
            if (!$value['nickname']) {
                $value['nickname'] = Base::cardFormat($value['email']);
            }
            // 头像
            $value['userimg'] = User::getAvatar($value['userid'], $value['userimg'], $value['email'], $value['nickname']);
            // 部门
            $value['department'] = array_filter(is_array($value['department']) ? $value['department'] : Base::explodeInt($value['department']));
        }
        return $value;
    }

    /**
     * userid 获取 基础信息
     * @param int $userid 会员ID
     * @return array|null
     */
    public static function userid2basic($userid)
    {
        $row = self::whereUserid($userid)->first();
        if (empty($row) || empty($row->cache)) {
            return null;
        }
        $cache = $row->cache;
        $cache = array_intersect_key($cache, array_flip(array_merge(User::$basicField, ['department_name'])));
        $cache['delete_at'] = $row->created_at->format($row->dateFormat ?: 'Y-m-d H:i:s');
        return $cache;
    }
}
