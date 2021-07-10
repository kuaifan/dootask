<?php

namespace App\Models;


use App\Module\Base;
use Illuminate\Database\Eloquent\SoftDeletes;
use Response;

/**
 * Class FileContent
 *
 * @package App\Models
 * @property int $id
 * @property int|null $fid 文件ID
 * @property string|null $content 内容
 * @property string|null $text 内容（主要用于文档类型搜索）
 * @property int|null $size 大小(B)
 * @property int|null $userid 会员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent newQuery()
 * @method static \Illuminate\Database\Query\Builder|FileContent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereFid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereUserid($value)
 * @method static \Illuminate\Database\Query\Builder|FileContent withTrashed()
 * @method static \Illuminate\Database\Query\Builder|FileContent withoutTrashed()
 * @mixin \Eloquent
 */
class FileContent extends AbstractModel
{
    use SoftDeletes;

    /**
     * 获取格式内容
     * @param $type
     * @param $content
     * @return array|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function formatContent($type, $content)
    {
        $content = Base::json2array($content);
        if (in_array($type, ['word', 'excel', 'ppt'])) {
            if (empty($content)) {
                return Response::download(resource_path('assets/statics/empty/empty.' . str_replace(['word', 'excel', 'ppt'], ['docx', 'xlsx', 'pptx'], $type)));
            }
            return Response::download(public_path($content['url']));
        }
        if (empty($content)) {
            switch ($type) {
                case 'document':
                    $content = [
                        "type" => "md",
                        "content" => "",
                    ];
                    break;

                case 'sheet':
                    $content = [
                        [
                            "name" => "Sheet1",
                            "config" => json_decode('{}'),
                        ]
                    ];
                    break;

                default:
                    $content = json_decode('{}');
                    break;
            }
        }
        return Base::retSuccess('success', [ 'content' => $content ]);
    }
}
