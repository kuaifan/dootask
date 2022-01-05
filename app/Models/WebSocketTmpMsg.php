<?php

namespace App\Models;

/**
 * App\Models\WebSocketTmpMsg
 *
 * @property int $id
 * @property string|null $md5 MD5(会员ID-消息)
 * @property string|null $msg 详细消息
 * @property int|null $send 是否已发送
 * @property int|null $create_id 所属会员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereCreateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketTmpMsg whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WebSocketTmpMsg extends AbstractModel
{

}
