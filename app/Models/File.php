<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class File
 *
 * @package App\Models
 * @property int $id
 * @property int|null $pid 上级ID
 * @property int|null $cid 复制ID
 * @property string|null $name 名称
 * @property string|null $type 类型
 * @property int|null $size 大小(B)
 * @property int|null $userid 拥有者ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File newQuery()
 * @method static \Illuminate\Database\Query\Builder|File onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|File query()
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUserid($value)
 * @method static \Illuminate\Database\Query\Builder|File withTrashed()
 * @method static \Illuminate\Database\Query\Builder|File withoutTrashed()
 * @mixin \Eloquent
 */
class File extends AbstractModel
{
    use SoftDeletes;

    /**
     * 遍历删除文件(夹)
     * @return bool
     */
    public function deleteFile()
    {
        AbstractModel::transaction(function () {
            $this->delete();
            FileContent::whereFid($this->id)->delete();
            $list = self::wherePid($this->id)->get();
            if ($list->isNotEmpty()) {
                foreach ($list as $item) {
                    $item->deleteFile();
                }
            }
        });
        return true;
    }
}
