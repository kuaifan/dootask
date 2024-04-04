<?php

namespace App\Models;

use App\Module\Base;
use Cache;

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
 * @property-read int $height
 * @property-read int $width
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
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
    protected $appends = [
        'width',
        'height',
    ];

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

    /**
     * 宽
     * @return int
     */
    public function getWidthAttribute()
    {
        $this->generateSizeData();
        return $this->appendattrs['width'];
    }

    /**
     * 高
     * @return int
     */
    public function getHeightAttribute()
    {
        $this->generateSizeData();
        return $this->appendattrs['height'];
    }

    /**
     * 生成尺寸数据
     */
    private function generateSizeData()
    {
        if (!isset($this->appendattrs['width'])) {
            $width = -1;
            $height = -1;
            if (in_array($this->ext, ['jpg', 'jpeg', 'webp', 'gif', 'png'])) {
                $path = public_path($this->getRawOriginal('path'));
                [$width, $height] = Cache::remember("File::size-" . md5($path), now()->addDays(7), function () use ($path) {
                    $width = -1;
                    $height = -1;
                    if (file_exists($path)) {
                        $paramet = getimagesize($path);
                        $width = $paramet[0];
                        $height = $paramet[1];
                    }
                    return [$width, $height];
                });
            }
            $this->appendattrs['width'] = $width;
            $this->appendattrs['height'] = $height;
        }
    }
}
