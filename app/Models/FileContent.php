<?php

namespace App\Models;


/**
 * Class FileContent
 *
 * @package App\Models
 * @property int $id
 * @property int|null $fid 文件ID
 * @property string|null $content 内容
 * @property string|null $text 内容（主要用于文档类型搜索）
 * @property int|null $userid 会员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereFid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileContent whereUserid($value)
 * @mixin \Eloquent
 */
class FileContent extends AbstractModel
{
}
