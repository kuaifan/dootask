<?php

namespace App\Models;

/**
 * App\Models\WebSocketDialogUser
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $userid 会员ID
 * @property string|null $top_at 置顶时间
 * @property int|null $mark_unread 是否标记为未读：0否，1是
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereMarkUnread($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereTopAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogUser extends AbstractModel
{

}
