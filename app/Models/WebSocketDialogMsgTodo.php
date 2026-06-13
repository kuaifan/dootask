<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\WebSocketDialogMsgTodo
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @property int|null $userid 接收会员ID
 * @property \Illuminate\Support\Carbon|null $done_at 完成时间
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
 * @property \Illuminate\Support\Carbon|null $remind_at 提醒时间
 * @property \Illuminate\Support\Carbon|null $reminded_at 已提醒时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebSocketDialogMsgTodo whereRemindAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebSocketDialogMsgTodo whereRemindedAt($value)
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

    /**
     * 取到点待提醒的待办行：有提醒时间、未提醒、未完成、提醒时间已到。
     * 纯查询，无副作用，供 TodoRemindTask 使用。
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function dueReminders()
    {
        return self::whereNotNull('remind_at')
            ->whereNull('reminded_at')
            ->whereNull('done_at')
            ->where('remind_at', '<=', Carbon::now())
            ->orderBy('msg_id')
            ->orderBy('id')
            ->limit(500)
            ->get();
    }
}
