<?php

namespace App\Models;

use App\Module\Base;
use App\Tasks\PushTask;
use Carbon\Carbon;

/**
 * Class WebSocketDialogMsg
 *
 * @package App\Models
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $userid 发送会员ID
 * @property string|null $type 消息类型
 * @property array|mixed $msg 详细消息
 * @property int|null $send 是否已送达
 * @property int|null $extra_int 额外数字参数
 * @property string|null $extra_str 额外字符参数
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereExtraInt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereExtraStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsg extends AbstractModel
{
    protected $hidden = [
        'updated_at',
    ];

    /**
     * 消息
     * @param $value
     * @return array|mixed
     */
    public function getMsgAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }

    /**
     * 标记已送达 同时 告诉发送人已送达
     * @return $this
     */
    public function sendSuccess()
    {
        if (empty($this->send)) {
            $this->send = 1;
            $this->save();
            PushTask::push([
                'userid' => $this->userid,
                'msg' => [
                    'type' => 'dialog',
                    'data' => $this->toArray(),
                ]
            ]);
        }
        return $this;
    }

    /**
     * 给会员添加并发送消息
     * @param int $dialog_id    会话ID（即 聊天室ID）
     * @param string $type      消息类型
     * @param array $msg        发送的消息
     * @param int $sender       发送的会员ID（默认自己，0为系统）
     * @param int $extra_int
     * @param string $extra_str
     * @return array
     */
    public static function addGroupMsg($dialog_id, $type, $msg, $sender = 0, $extra_int = 0, $extra_str = '')
    {
        $dialogMsg = self::createInstance([
            'userid' => $sender ?: User::token2userid(),
            'type' => $type,
            'msg' => $msg,
            'extra_int' => $extra_int,
            'extra_str' => $extra_str,
        ]);
        return AbstractModel::transaction(function () use ($dialog_id, $msg, $dialogMsg) {
            $dialog = WebSocketDialog::checkGroupDialog($dialogMsg->userid, $dialog_id);
            if (empty($dialog)) {
                return Base::retError('不是聊天室成员');
            }
            $dialog->last_at = Carbon::now();
            $dialog->save();
            $dialogMsg->dialog_id = $dialog->id;
            $dialogMsg->save();
            //
            $userids = WebSocketDialogUser::whereDialogId($dialog->id)->where('userid', '!=', $dialogMsg->userid)->pluck('userid')->toArray();
            if ($userids) {
                PushTask::push([
                    'userid' => $userids,
                    'msg' => [
                        'type' => 'dialog',
                        'msgId' => $dialogMsg->id,
                        'data' => $dialogMsg->toArray(),
                    ]
                ]);
            }
            return Base::retSuccess('发送成功', $dialogMsg);
        });
    }


    /**
     * 给会员添加并发送消息
     * @param int $userid       接收的会员ID
     * @param string $type      消息类型
     * @param array $msg        发送的消息
     * @param int $sender       发送的会员ID（默认自己，0为系统）
     * @param int $extra_int
     * @param string $extra_str
     * @return array
     */
    public static function addUserMsg($userid, $type, $msg, $sender = 0, $extra_int = 0, $extra_str = '')
    {
        $dialogMsg = self::createInstance([
            'userid' => $sender ?: User::token2userid(),
            'type' => $type,
            'msg' => $msg,
            'extra_int' => $extra_int,
            'extra_str' => $extra_str,
        ]);
        return AbstractModel::transaction(function () use ($userid, $msg, $dialogMsg) {
            $dialog = WebSocketDialog::checkUserDialog($dialogMsg->userid, $userid);
            if (empty($dialog)) {
                return Base::retError('创建对话失败');
            }
            $dialog->last_at = Carbon::now();
            $dialog->save();
            $dialogMsg->dialog_id = $dialog->id;
            $dialogMsg->save();
            //
            PushTask::push([
                'userid' => $userid,
                'msg' => [
                    'type' => 'dialog',
                    'msgId' => $dialogMsg->id,
                    'data' => $dialogMsg->toArray(),
                ]
            ]);
            return Base::retSuccess('发送成功', $dialogMsg);
        });
    }

}
