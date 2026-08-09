<?php

namespace App\Models;

/**
 * @property int $id
 * @property int $msg_id 消息ID
 * @property int $dialog_id 会话ID
 * @property string $source_type 来源类型
 * @property string $kind 附件类型
 * @property int $position 消息内附件顺序
 * @property string $name 附件名称
 * @property string $ext 文件扩展名
 * @property string|null $path 原文件地址
 * @property string|null $thumb 缩略图地址
 * @property int $size 文件大小(B)
 * @property int $width 图片宽度
 * @property int $height 图片高度
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgAttachment whereMsgId($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsgAttachment extends AbstractModel
{
    public const SOURCE_FILE_MESSAGE = 'file_message';
    public const SOURCE_INLINE_IMAGE = 'inline_image';

    public const KIND_FILE = 'file';
    public const KIND_IMAGE = 'image';

    protected $fillable = [
        'msg_id',
        'dialog_id',
        'source_type',
        'kind',
        'position',
        'name',
        'ext',
        'path',
        'thumb',
        'size',
        'width',
        'height',
    ];
}
