<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\WebSocketDialogMsgExtra
 *
 * @property int $id
 * @property int|null $msg_id 消息ID
 * @property string|null $data 长内容
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WebSocketDialogMsg|null $webSocketDialogMsg
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgExtra whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsgExtra extends AbstractModel
{
    /**
     * @param $value
     * @return array
     */
    public function getDataAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }


    /**
     * 关联到消息
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function webSocketDialogMsg(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WebSocketDialogMsg::class, 'msg_id', 'id');
    }
}

