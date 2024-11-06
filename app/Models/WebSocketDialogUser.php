<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\WebSocketDialogUser
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $userid 会员ID
 * @property \Illuminate\Support\Carbon|null $top_at 置顶时间
 * @property \Illuminate\Support\Carbon|null $last_at 最后消息时间
 * @property int|null $mark_unread 是否标记为未读：0否，1是
 * @property int|null $silence 是否免打扰：0否，1是
 * @property int|null $hide 不显示会话：0否，1是
 * @property int|null $inviter 邀请人
 * @property int|null $important 是否不可移出（项目、任务、部门人员）
 * @property string|null $color 颜色
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WebSocketDialog|null $webSocketDialog
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereHide($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereImportant($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereInviter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereMarkUnread($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereSilence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereTopAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogUser whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogUser extends AbstractModel
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webSocketDialog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WebSocketDialog::class, 'id', 'dialog_id');
    }

    /**
     * 更新对话最后消息时间
     * @return WebSocketDialogMsg|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public static function updateMsgLastAt($dialogId)
    {
        $lastMsg = WebSocketDialogMsg::whereDialogId($dialogId)->orderByDesc('id')->first();
        if ($lastMsg) {
            WebSocketDialogUser::whereDialogId($dialogId)->change(['last_at' => $lastMsg->created_at]);
        }
        return $lastMsg;
    }
}
