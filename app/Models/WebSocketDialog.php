<?php

namespace App\Models;

use App\Module\Base;

/**
 * Class WebSocketDialog
 *
 * @package App\Models
 * @property int $id
 * @property string|null $type 对话类型
 * @property string|null $group_type 聊天室类型
 * @property string|null $name 对话名称
 * @property string|null $last_at 最后消息时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereDeletedAt($value)
 */
class WebSocketDialog extends AbstractModel
{
    /**
     * 格式化对话
     * @param WebSocketDialog $dialog
     * @param int $userid   会员ID
     * @return WebSocketDialog
     */
    public static function formatData(WebSocketDialog $dialog, $userid)
    {
        // 最后消息
        $last_msg = WebSocketDialogMsg::whereDialogId($dialog->id)->orderByDesc('id')->first();
        $dialog->last_msg = $last_msg;
        // 未读信息
        $dialog->unread = WebSocketDialogMsgRead::whereDialogId($dialog->id)->whereUserid($userid)->whereReadAt(null)->count();
        // 对话人数
        $builder = WebSocketDialogUser::whereDialogId($dialog->id);
        $dialog->people = $builder->count();
        // 对方信息
        $dialog->dialog_user = null;
        if ($dialog->type === 'user') {
            $dialog_user = $builder->where('userid', '!=', $userid)->first();
            $dialog->name = User::userid2nickname($dialog_user->userid);
            $dialog->dialog_user = $dialog_user;
        }
        return $dialog;
    }

    /**
     * 创建聊天室
     * @param string $name          聊天室名称
     * @param int|array $userid     加入的会员ID或会员ID组
     * @param string $group_type    聊天室类型
     * @return self|null
     */
    public static function createGroup($name, $userid, $group_type = '')
    {
        $result = AbstractModel::transaction(function () use ($userid, $group_type, $name) {
            $dialog = self::createInstance([
                'type' => 'group',
                'name' => $name,
                'group_type' => $group_type,
            ]);
            $dialog->save();
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    WebSocketDialogUser::createInstance([
                        'dialog_id' => $dialog->id,
                        'userid' => $value,
                    ])->save();
                }
            }
            return Base::retSuccess('success', $dialog);
        });
        return Base::isSuccess($result) ? $result['data'] : null;
    }

    /**
     * 加入聊天室
     * @param int $dialog_id        会话ID（即 聊天室ID）
     * @param int|array $userid     加入的会员ID或会员ID组
     * @return bool
     */
    public static function joinGroup($dialog_id, $userid)
    {
        $dialog = self::whereId($dialog_id)->whereType('group')->first();
        if (empty($dialog)) {
            return false;
        }
        $result = AbstractModel::transaction(function () use ($dialog, $userid) {
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    WebSocketDialogUser::createInstance([
                        'dialog_id' => $dialog->id,
                        'userid' => $value,
                    ])->save();
                }
            }
        });
        return Base::isSuccess($result);
    }

    /**
     * 退出聊天室
     * @param int $dialog_id        会话ID（即 聊天室ID）
     * @param int|array $userid     加入的会员ID或会员ID组
     * @return bool
     */
    public static function exitGroup($dialog_id, $userid)
    {
        if (is_array($userid)) {
            WebSocketDialogUser::whereDialogId($dialog_id)->whereIn('userid', $userid)->delete();
        } else {
            WebSocketDialogUser::whereDialogId($dialog_id)->whereUserid($userid)->delete();
        }
        return true;
    }

    /**
     * 获取聊天室对话
     * @param int $userid       会员ID
     * @param int $dialog_id    会话ID（即 聊天室ID）
     * @return self
     */
    public static function checkGroupDialog($userid, $dialog_id)
    {
        if ($userid == 0) {
            return self::whereId($dialog_id)->first();
        } else {
            return self::select(['web_socket_dialogs.*'])
                ->join('web_socket_dialog_users', 'web_socket_dialog_users.dialog_id', '=', 'web_socket_dialogs.id')
                ->where('web_socket_dialogs.id', $dialog_id)
                ->where('web_socket_dialogs.type', 'group')
                ->where('web_socket_dialog_users.userid', $userid)
                ->first();
        }
    }

    /**
     * 获取会员对话（没有自动创建）
     * @param int $userid   会员ID
     * @param int $userid2  另一个会员ID
     * @return self|null
     */
    public static function checkUserDialog($userid, $userid2)
    {
        $dialogUser = self::select(['web_socket_dialogs.*'])
            ->join('web_socket_dialog_users', 'web_socket_dialog_users.dialog_id', '=', 'web_socket_dialogs.id')
            ->where('web_socket_dialogs.type', 'user')
            ->whereIn('web_socket_dialog_users.userid', [$userid, $userid2])
            ->get();
        if ($dialogUser->count() >= 2) {
            return $dialogUser[0];
        }
        $result = AbstractModel::transaction(function () use ($userid2, $userid) {
            $dialog = self::createInstance([
                'type' => 'user',
            ]);
            $dialog->save();
            WebSocketDialogUser::createInstance([
                'dialog_id' => $dialog->id,
                'userid' => $userid,
            ])->save();
            WebSocketDialogUser::createInstance([
                'dialog_id' => $dialog->id,
                'userid' => $userid2,
            ])->save();
            return Base::retSuccess('success', $dialog);
        });
        return Base::isSuccess($result) ? $result['data'] : null;
    }

}
