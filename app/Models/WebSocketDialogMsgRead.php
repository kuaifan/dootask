<?php

namespace App\Models;

/**
 * Class WebSocketDialogMsgRead
 *
 * @package App\Models
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @property int|null $userid 发送会员ID
 * @property string|null $read_at 阅读时间
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsgRead extends AbstractModel
{
    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }
}
