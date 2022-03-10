<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\WebSocketDialogMsgRead
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @property int|null $userid 发送会员ID
 * @property int|null $after 在阅读之后才添加的记录
 * @property string|null $read_at 阅读时间
 * @property-read \App\Models\WebSocketDialogMsg|null $webSocketDialogMsg
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereAfter($value)
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webSocketDialogMsg(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WebSocketDialogMsg::class, 'id', 'msg_id');
    }

    /**
     * 仅标记成阅读
     * @param $list
     * @return void
     */
    public static function onlyMarkRead($list)
    {
        $dialogMsg = [];
        /** @var WebSocketDialogMsgRead $item */
        foreach ($list as $item) {
            $item->read_at = Carbon::now();
            $item->save();
            if (isset($dialogMsg[$item->msg_id])) {
                $dialogMsg[$item->msg_id]['readNum']++;
            } else {
                $dialogMsg[$item->msg_id] = [
                    'dialogMsg' => $item->webSocketDialogMsg,
                    'readNum' => 1
                ];
            }
        }
        foreach ($dialogMsg as $item) {
            $item['dialogMsg']?->generatePercentage($item['readNum']);
        }
    }
}
