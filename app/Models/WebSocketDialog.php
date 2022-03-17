<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\WebSocketDialog
 *
 * @property int $id
 * @property string|null $type 对话类型
 * @property string|null $group_type 聊天室类型
 * @property string|null $name 对话名称
 * @property string|null $last_at 最后消息时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WebSocketDialogUser[] $dialogUser
 * @property-read int|null $dialog_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newQuery()
 * @method static \Illuminate\Database\Query\Builder|WebSocketDialog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|WebSocketDialog withTrashed()
 * @method static \Illuminate\Database\Query\Builder|WebSocketDialog withoutTrashed()
 * @mixin \Eloquent
 */
class WebSocketDialog extends AbstractModel
{
    use SoftDeletes;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dialogUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebSocketDialogUser::class, 'dialog_id', 'id');
    }

    /**
     * 删除会话
     * @return bool
     */
    public function deleteDialog()
    {
        AbstractModel::transaction(function () {
            WebSocketDialogMsgRead::whereDialogId($this->id)
                ->whereNull('read_at')
                ->chunkById(100, function ($list) {
                    WebSocketDialogMsgRead::onlyMarkRead($list);
                });
            $this->delete();
        });
        return true;
    }

    /**
     * 还原会话
     * @return bool
     */
    public function recoveryDialog()
    {
        $this->restore();
        return true;
    }

    /**
     * 获取对话（同时检验对话身份）
     * @param $dialog_id
     * @return self
     */
    public static function checkDialog($dialog_id)
    {
        $dialog = WebSocketDialog::find($dialog_id);
        if (empty($dialog)) {
            throw new ApiException('对话不存在或已被删除', ['dialog_id' => $dialog_id], -4003);
        }
        //
        $userid = User::userid();
        if ($dialog->type === 'group' && $dialog->group_type === 'task') {
            // 任务群对话校验是否在项目内
            $project_id = intval(ProjectTask::whereDialogId($dialog->id)->value('project_id'));
            if ($project_id > 0) {
                if (ProjectUser::whereProjectId($project_id)->whereUserid($userid)->exists()) {
                    return $dialog;
                }
            }
        }
        if (!WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($userid)->exists()) {
            throw new ApiException('不在成员列表内');
        }
        return $dialog;
    }

    /**
     * 格式化对话
     * @param WebSocketDialog $dialog
     * @param int $userid   会员ID
     * @return self|null
     */
    public static function formatData(WebSocketDialog $dialog, $userid)
    {
        if (empty($dialog)) {
            return null;
        }
        // 最后消息
        $last_msg = WebSocketDialogMsg::whereDialogId($dialog->id)->orderByDesc('id')->first();
        $dialog->last_msg = $last_msg;
        // 未读信息
        $dialog->unread = WebSocketDialogMsgRead::whereDialogId($dialog->id)->whereUserid($userid)->whereReadAt(null)->count();
        $dialog->mark_unread = $dialog->mark_unread ?? WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($userid)->value('mark_unread');
        // 对话人数
        $builder = WebSocketDialogUser::whereDialogId($dialog->id);
        $dialog->people = $builder->count();
        // 对方信息
        $dialog->dialog_user = null;
        $dialog->group_info = null;
        $dialog->top_at = $dialog->top_at ?? WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($userid)->value('top_at');
        switch ($dialog->type) {
            case "user":
                $dialog_user = $builder->where('userid', '!=', $userid)->first();
                $dialog->name = User::userid2nickname($dialog_user->userid);
                $dialog->dialog_user = $dialog_user;
                break;
            case "group":
                if ($dialog->group_type === 'project') {
                    $dialog->group_info = Project::withTrashed()->select(['id', 'name', 'archived_at', 'deleted_at'])->whereDialogId($dialog->id)->first()?->cancelAppend()->cancelHidden();
                    $dialog->name = $dialog->group_info ? $dialog->group_info->name : '';
                } elseif ($dialog->group_type === 'task') {
                    $dialog->group_info = ProjectTask::withTrashed()->select(['id', 'name', 'complete_at', 'archived_at', 'deleted_at'])->whereDialogId($dialog->id)->first()?->cancelAppend()->cancelHidden();
                    $dialog->name = $dialog->group_info ? $dialog->group_info->name : '';
                }
                break;
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
        return AbstractModel::transaction(function () use ($userid, $group_type, $name) {
            $dialog = self::createInstance([
                'type' => 'group',
                'name' => $name ?: '',
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
            return $dialog;
        });
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
        AbstractModel::transaction(function () use ($dialog, $userid) {
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    WebSocketDialogUser::createInstance([
                        'dialog_id' => $dialog->id,
                        'userid' => $value,
                    ])->save();
                }
            }
        });
        return true;
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
     * 获取会员对话（没有自动创建）
     * @param int $userid   会员ID
     * @param int $userid2  另一个会员ID
     * @return self|null
     */
    public static function checkUserDialog($userid, $userid2)
    {
        $dialogUser = self::select(['web_socket_dialogs.*'])
            ->join('web_socket_dialog_users as u1', 'web_socket_dialogs.id', '=', 'u1.dialog_id')
            ->join('web_socket_dialog_users as u2', 'web_socket_dialogs.id', '=', 'u2.dialog_id')
            ->where('u1.userid', $userid)
            ->where('u2.userid', $userid2)
            ->where('web_socket_dialogs.type', 'user')
            ->first();
        if ($dialogUser) {
            return $dialogUser;
        }
        return AbstractModel::transaction(function () use ($userid2, $userid) {
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
            return $dialog;
        });
    }

}
