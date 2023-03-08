<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\Deleted
 *
 * @property int $id
 * @property string|null $type 删除的数据类型（如：project、task、dialog）
 * @property int|null $did 删除的数据ID
 * @property int|null $userid 关系会员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted whereDid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deleted whereUserid($value)
 * @mixin \Eloquent
 */
class Deleted extends AbstractModel
{
    const UPDATED_AT = null;

    /**
     * 获取删除的ID
     * @param $type
     * @param $userid
     * @param $time
     * @return array
     */
    public static function ids($type, $userid, $time): array
    {
        if (empty($time)) {
            return [];
        }
        return self::where([
            'type' => $type,
            'userid' => $userid
        ])->where('created_at', '>=', Carbon::parse($time))->pluck('did')->toArray();
    }

    /**
     * 忘记（恢复或添加数据时删除记录）
     * @param $type
     * @param $id
     * @param $userid
     * @return void
     */
    public static function forget($type, $id, $userid): void
    {
        if (is_array($userid)) {
            self::where([
                'type' => $type,
                'did' => $id,
            ])->whereIn('userid', $userid)->delete();
        } else {
            self::where([
                'type' => $type,
                'did' => $id,
                'userid' => $userid,
            ])->delete();
        }
    }

    /**
     * 记录（删除数据时添加记录）
     * @param $type
     * @param $id
     * @param $userid
     * @return void
     */
    public static function record($type, $id, $userid): void
    {
        $array = is_array($userid) ? $userid : [$userid];
        foreach ($array as $value) {
            self::updateInsert([
                'type' => $type,
                'did' => $id,
                'userid' => $value,
            ]);
        }
    }
}
