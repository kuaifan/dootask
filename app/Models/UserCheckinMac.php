<?php


namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;

/**
 * App\Models\UserCheckinMac
 *
 * @property int $id
 * @property int|null $userid 会员id
 * @property string|null $mac MAC地址
 * @property string|null $remark 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac whereMac($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinMac whereUserid($value)
 * @mixin \Eloquent
 */
class UserCheckinMac extends AbstractModel
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
                if (self::whereMac($item['mac'])->where('userid', '!=', $userid)->exists()) {
                    throw new ApiException("{$item['mac']} 已被其他成员设置");
                }
                $update = [];
                if ($item['remark']) {
                    $update = [
                        'remark' => $item['remark']
                    ];
                }
                $row = self::updateInsert([
                    'userid' => $userid,
                    'mac' => $item['mac']
                ], $update);
                if ($row) {
                    $ids[] = $row->id;
                    $list[] = $row;
                }
            }
            self::whereUserid($userid)->whereNotIn('id', $ids)->delete();
            //
            return Base::retSuccess('修改成功', $list);
        });
    }
}
