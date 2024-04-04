<?php

namespace App\Models;


use App\Module\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\FileContent
 *
 * @property int $id
 * @property int|null $fid 文件ID
 * @property string|null $content 内容
 * @property string|null $text 内容（主要用于文档类型搜索）
 * @property int|null $size 大小(B)
 * @property int|null $userid 会员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereFid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent withoutTrashed()
 * @mixin \Eloquent
 */
class FileContent extends AbstractModel
{
    use SoftDeletes;

    /**
     * 强制删除文件内容
     * @return void
     */
    public function forceDeleteContent()
    {
        $this->forceDelete();
        $content = Base::json2array($this->content ?: []);
        if (str_starts_with($content['url'], 'uploads/')) {
            $path = public_path($content['url']);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * 转预览地址
     * @param array $array
     * @return string
     */
    public static function toPreviewUrl($array)
    {
        $fileExt = $array['ext'];
        $fileName = $array['name'];
        $filePath = $array['path'];
        $name = Base::rightDelete($fileName, ".{$fileExt}") . ".{$fileExt}";
        $key = urlencode(Base::urlAddparameter($filePath, [
            'name' => $name,
            'ext' => $fileExt
        ]));
        return Base::fillUrl("online/preview/{$name}?key={$key}&version=" . Base::getVersion() . "&__=" . Base::msecTime());
    }

    /**
     * 转预览地址
     * @param File $file
     * @param $content
     * @return string
     */
    public static function formatPreview($file, $content)
    {
        $content = Base::json2array($content ?: []);
        $filePath = $content['url'];
        if (in_array($file->type, ['word', 'excel', 'ppt'])) {
            if (empty($content)) {
                $filePath = 'assets/office/empty.' . str_replace(['word', 'excel', 'ppt'], ['docx', 'xlsx', 'pptx'], $file->type);
            }
        }
        return self::toPreviewUrl([
            'ext' => $file->ext,
            'name' => $file->name,
            'path' => $filePath,
        ]);
    }

    /**
     * 获取格式内容（或下载）
     * @param File $file
     * @param $content
     * @param $download
     * @return array|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function formatContent($file, $content, $download = false)
    {
        $name = $file->ext ? "{$file->name}.{$file->ext}" : null;
        $content = Base::json2array($content ?: []);
        if (in_array($file->type, ['word', 'excel', 'ppt'])) {
            if (empty($content)) {
                $filePath = public_path('assets/office/empty.' . str_replace(['word', 'excel', 'ppt'], ['docx', 'xlsx', 'pptx'], $file->type));
            } else {
                $filePath = public_path($content['url']);
            }
            return Base::streamDownload($filePath, $name);
        }
        if (empty($content)) {
            $content = match ($file->type) {
                'document' => [
                    "type" => $file->ext,
                    "content" => "",
                ],
                default => json_decode('{}'),
            };
            if ($download) {
                abort(403, "This file is empty.");
            }
        } else {
            $path = $content['url'];
            if ($file->ext) {
                $res = File::formatFileData([
                    'path' => $path,
                    'ext' => $file->ext,
                    'size' => $file->size,
                    'name' => $file->name,
                ]);
                $content = $res['content'];
            } else {
                $content['preview'] = false;
            }
            if ($download) {
                $filePath = public_path($path);
                if (isset($filePath)) {
                    return Base::streamDownload($filePath, $name);
                } else {
                    abort(403, "This file not support download.");
                }
            }
        }
        return Base::retSuccess('success', [ 'content' => $content ]);
    }
}
