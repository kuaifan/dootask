<?php

namespace App\Models;

use App\Module\Base;

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
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileLink query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
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

    /**
     * 生成链接
     * @param $fileId
     * @param $userid
     * @param $refresh
     * @return array
     */
    public static function generateLink($fileId, $userid, $refresh = false)
    {
        $fileLink = FileLink::whereFileId($fileId)->whereUserid($userid)->first();
        if (empty($fileLink)) {
            $fileLink = FileLink::createInstance([
                'file_id' => $fileId,
                'userid' => $userid,
                'code' => base64_encode("{$fileId},{$userid}," . Base::generatePassword()),
            ]);
            $fileLink->save();
        } else {
            if ($refresh == 'yes') {
                $fileLink->code = base64_encode("{$fileId},{$userid}," . Base::generatePassword());
                $fileLink->save();
            }
        }
        return [
            'id' => $fileId,
            'url' => Base::fillUrl('single/file/' . $fileLink->code),
            'code' => $fileLink->code,
            'num' => $fileLink->num
        ];
    }
}
