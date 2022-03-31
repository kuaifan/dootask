<?php

namespace App\Models;

/**
 * App\Models\FileLink
 *
 * @property int $id
 * @property int|null $file_id 文件ID
 * @property int|null $num 累计访问
 * @property string|null $code 链接码
 * @property int|null $userid 会员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\File|null $file
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink whereUserid($value)
 * @mixin \Eloquent
 */
class FileLink extends AbstractModel
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function file(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
}
