<?php

namespace App\Models;


/**
 * App\Models\FileUser
 *
 * @property int $id
 * @property int|null $file_id 项目ID
 * @property int|null $userid 成员ID
 * @property int|null $permission 权限：0只读，1读写
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser wherePermission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereUserid($value)
 * @mixin \Eloquent
 */
class FileUser extends AbstractModel
{
    /**
     * 删除所有共享成员（同时删除成员分享的链接）
     * @param $file_id
     * @param int $retain_link_userid       保留指定会员的链接
     * @return mixed
     */
    public static function deleteFileAll($file_id, $retain_link_userid = 0)
    {
        return AbstractModel::transaction(function() use ($retain_link_userid, $file_id) {
            if ($retain_link_userid > 0) {
                FileLink::whereFileId($file_id)->where('userid', '!=', $retain_link_userid)->delete();
            } else {
                FileLink::whereFileId($file_id)->delete();
            }
            FileUser::whereFileId($file_id)->delete();
        });
    }
    /**
     * 删除指定共享成员（同时删除成员分享的链接）
     * @param $file_id
     * @param $userid
     * @return mixed
     */
    public static function deleteFileUser($file_id, $userid)
    {
        return AbstractModel::transaction(function() use ($userid, $file_id) {
            FileLink::whereFileId($file_id)->whereUserid($userid)->delete();
            return self::whereFileId($file_id)->whereUserid($userid)->delete();
        });
    }
}
