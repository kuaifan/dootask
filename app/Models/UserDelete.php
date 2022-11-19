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
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDelete query()
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
            if ($value['userimg'] && !str_contains($value['userimg'], 'avatar/')) {
                $value['userimg'] = Base::fillUrl($value['userimg']);
            } else if (User::$defaultAvatarMode === 'auto') {
                $value['userimg'] = url("avatar/" . urlencode($value['nickname']) . ".png");
            } else {
                $name = ($value['userid'] - 1) % 21 + 1;
                $value['userimg'] = url("images/avatar/default_{$name}.png");
            }
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
        $cache = array_intersect_key($cache, array_flip(User::$basicField));
        $cache['delete_at'] = $row->created_at->format($row->dateFormat ?: 'Y-m-d H:i:s');
        $cache['department_name'] = $cache['department'] ? UserDepartment::whereIn('id', $cache['department'])->pluck('name')->implode(',') : '';
        return $cache;
    }
}
