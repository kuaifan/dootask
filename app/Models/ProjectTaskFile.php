<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\ProjectTaskFile
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property string|null $name 文件名称
 * @property int|null $size 文件大小(B)
 * @property string|null $ext 文件格式
 * @property string $path 文件地址
 * @property string $thumb 缩略图
 * @property int|null $userid 上传用户ID
 * @property int|null $download 下载次数
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereDownload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereExt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereThumb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskFile extends AbstractModel
{
    /**
     * 地址
     * @param $value
     * @return string
     */
    public function getPathAttribute($value)
    {
        return Base::fillUrl($value);
    }

    /**
     * 缩略图
     * @param $value
     * @return string
     */
    public function getThumbAttribute($value)
    {
        return Base::fillUrl($value ?: Base::extIcon($this->ext));
    }
}
