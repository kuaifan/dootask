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
    /**
     * 保存mac地址
     * @param $userid
     * @param $array
     * @return mixed
     */
    public static function saveMac($userid, $array)
    {
        return AbstractModel::transaction(function() use ($array, $userid) {
            $ids = [];
            $list = [];
            foreach ($array as $item) {
                $update = [];
                if ($item['remark']) {
                    $update = [
                        'remark' => $item['remark']
                    ];
                }
                $row = UserCheckin::updateInsert([
                    'userid' => $userid,
                    'mac' => $item['mac']
                ], $update);
                if ($row) {
                    $ids[] = $row->id;
                    $list[] = $row;
                }
            }
            UserCheckin::whereUserid($userid)->whereNotIn('id', $ids)->delete();
            //
            return Base::retSuccess('修改成功', $list);
        });
    }
}
