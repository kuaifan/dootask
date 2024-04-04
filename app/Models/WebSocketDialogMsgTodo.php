<?php

namespace App\Models;

/**
 * App\Models\WebSocketDialogMsgTodo
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @property int|null $userid 接收会员ID
 * @property string|null $done_at 完成时间
 * @property-read array|mixed $msg_data
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo whereDoneAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgTodo whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsgTodo extends AbstractModel
{
    protected $appends = [
        'msg_data',
    ];

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }

    /**
     * 消息详情
     * @return array|mixed
     */
    public function getMsgDataAttribute()
    {
        if (!isset($this->appendattrs['msgData'])) {
            $this->appendattrs['msgData'] = WebSocketDialogMsg::select(['id', 'type', 'msg'])->whereId($this->msg_id)->first()?->cancelAppend();
        }
        return $this->appendattrs['msgData'];
    }
}
