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
 * @property string|null $msg 详细消息
 * @property int|null $send 是否已送达
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsg extends AbstractModel
{
    /**
     * 给会员添加并发送消息
     * @param int $dialog_id    会话ID（即 聊天室ID）
     * @param array $msg        发送的消息
     * @param int $sender       发送的会员ID（默认自己，0为系统）
     * @return array|bool
     */
    public static function addGroupMsg($dialog_id, $msg, $sender = 0)
    {
        $dialogMsg = self::createInstance([
            'userid' => $sender ?: User::token2userid(),
            'msg' => $msg,
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
            $userids = WebSocketDialogUser::whereDialogId($dialog->id)->where('userid', '!=', $dialogMsg->userid)->pluck('userid');
            if ($userids) {
                PushTask::push([
                    'userid' => $userids,
                    'msg' => [
                        'type' => 'dialog',
                        'data' => $msg,
                    ]
                ]);
            }
            return Base::retSuccess('发送成功');
        });
    }


    /**
     * 给会员添加并发送消息
     * @param int $userid       接收的会员ID
     * @param array $msg        发送的消息
     * @param int $sender       发送的会员ID（默认自己，0为系统）
     * @return array|bool
     */
    public static function addUserMsg($userid, $msg, $sender = 0)
    {
        $dialogMsg = self::createInstance([
            'userid' => $sender ?: User::token2userid(),
            'msg' => $msg,
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
                    'data' => $msg,
                ]
            ]);
            return Base::retSuccess('发送成功');
        });
    }

}
