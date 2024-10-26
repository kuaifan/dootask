<?php

namespace App\Models;

/**
 * App\Models\WebSocketDialogMsgTranslate
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @property string|null $language 语言
 * @property string|null $content 翻译内容
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTranslate whereMsgId($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsgTranslate extends AbstractModel
{
    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }
}
