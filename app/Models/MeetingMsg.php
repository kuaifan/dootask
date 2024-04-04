<?php

namespace App\Models;

/**
 * App\Models\MeetingMsg
 *
 * @property int $id
 * @property string|null $meetingid 会议ID
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg whereMeetingid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MeetingMsg whereMsgId($value)
 * @mixin \Eloquent
 */
class MeetingMsg extends AbstractModel
{
    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }
}
