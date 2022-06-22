<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Tasks\PushTask;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\WebSocketDialog
 *
 * @property int $id
 * @property string|null $type 对话类型
 * @property string|null $group_type 聊天室类型
 * @property string|null $name 对话名称
 * @property string|null $last_at 最后消息时间
 * @property int|null $owner_id 群主用户ID
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
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereOwnerId($value)
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
     * 格式化对话
     * @param int $userid   会员ID
     * @return $this
     */
    public function formatData($userid)
    {
        if (isset($this->search_msg_id)) {
            // 最后消息 (搜索预览消息)
            $this->last_msg = WebSocketDialogMsg::whereDialogId($this->id)->find($this->search_msg_id);
            $this->last_at = $this->last_msg?->created_at;
        } else {
            // 最后消息
            $this->last_msg = WebSocketDialogMsg::whereDialogId($this->id)->orderByDesc('id')->first();
            // 未读信息
            $unreadBuilder = WebSocketDialogMsgRead::whereDialogId($this->id)->whereUserid($userid)->whereReadAt(null);
            $this->unread = $unreadBuilder->count();
            $this->mention = 0;
            $this->last_umid = 0;
            if ($this->unread > 0) {
                $this->mention = $unreadBuilder->clone()->whereMention(1)->count();
                $this->last_umid = intval($unreadBuilder->clone()->orderByDesc('msg_id')->value('msg_id'));
            }
            $this->mark_unread = $this->mark_unread ?? WebSocketDialogUser::whereDialogId($this->id)->whereUserid($userid)->value('mark_unread');
            // 对话人数
            $builder = WebSocketDialogUser::whereDialogId($this->id);
            $this->people = $builder->count();
        }
        // 对方信息
        $this->dialog_user = null;
        $this->group_info = null;
        $this->top_at = $this->top_at ?? WebSocketDialogUser::whereDialogId($this->id)->whereUserid($userid)->value('top_at');
        switch ($this->type) {
            case "user":
                $dialog_user = WebSocketDialogUser::whereDialogId($this->id)->where('userid', '!=', $userid)->first();
                if ($dialog_user->userid === 0) {
                    $dialog_user->userid = $userid;
                }
                $basic = User::userid2basic($dialog_user->userid);
                if ($basic) {
                    $this->name = $basic->nickname;
                } else {
                    $this->name = 'non-existent';
                    $this->dialog_delete = 1;
                }
                $this->dialog_user = $dialog_user;
                break;
            case "group":
                if ($this->group_type === 'project') {
                    $this->group_info = Project::withTrashed()->select(['id', 'name', 'archived_at', 'deleted_at'])->whereDialogId($this->id)->first()?->cancelAppend()->cancelHidden();
                    if ($this->group_info) {
                        $this->name = $this->group_info->name;
                    } else {
                        $this->name = '[Delete]';
                        $this->dialog_delete = 1;
                    }
                } elseif ($this->group_type === 'task') {
                    $this->group_info = ProjectTask::withTrashed()->select(['id', 'name', 'complete_at', 'archived_at', 'deleted_at'])->whereDialogId($this->id)->first()?->cancelAppend()->cancelHidden();
                    if ($this->group_info) {
                        $this->name = $this->group_info->name;
                    } else {
                        $this->name = '[Delete]';
                        $this->dialog_delete = 1;
                    }
                }
                break;
        }
        return $this;
    }

    /**
     * 加入聊天室
     * @param int|array $userid     加入的会员ID或会员ID组
     * @return bool
     */
    public function joinGroup($userid)
    {
        AbstractModel::transaction(function () use ($userid) {
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    WebSocketDialogUser::updateInsert([
                        'dialog_id' => $this->id,
                        'userid' => $value,
                    ], [
                        'inviter' => User::userid(),
                    ]);
                }
            }
        });
        return true;
    }

    /**
     * 退出聊天室
     * @param int|array $userid     加入的会员ID或会员ID组
     * @param $type
     */
    public function exitGroup($userid, $type = 'exit')
    {
        $typeDesc = $type === 'remove' ? '移出' : '退出';
        AbstractModel::transaction(function () use ($typeDesc, $type, $userid) {
            $builder = WebSocketDialogUser::whereDialogId($this->id);
            if (is_array($userid)) {
                $builder->whereIn('userid', $userid);
            } else {
                $builder->whereUserid($userid);
            }
            $builder->chunkById(100, function($list) use ($typeDesc, $type) {
                /** @var WebSocketDialogUser $item */
                foreach ($list as $item) {
                    if ($type === 'remove' && !in_array(User::userid(), [$this->owner_id, $item->inviter])) {
                        throw new ApiException('只有群主或邀请人可以移出成员');
                    }
                    if ($item->userid == $this->owner_id) {
                        throw new ApiException('群主不可' . $typeDesc);
                    }
                    if ($item->important) {
                        throw new ApiException('项目人员或任务人员不可' . $typeDesc);
                    }
                    $item->delete();
                }
            });
        });
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
     * 检查群组类型
     * @param string|array|null $groupType
     * @return void
     */
    public function checkGroup($groupType = null)
    {
        if ($this->type !== 'group') {
            throw new ApiException('仅限群组操作');
        }
        if ($groupType) {
            $groupTypes = is_array($groupType) ? $groupType : [$groupType];
            if (!in_array($this->group_type, $groupTypes)) {
                throw new ApiException('操作的群组类型错误');
            }
        }
    }

    /**
     * 获取群组名称
     * @return mixed|string|null
     */
    public function getGroupName()
    {
        if (!isset($this->appendattrs['groupName'])) {
            $name = $this->name;
            if ($this->type == "group") {
                if ($this->group_type === 'project') {
                    $name = \DB::table('projects')->where('dialog_id', $this->id)->value('name');
                } elseif ($this->group_type === 'task') {
                    $name = \DB::table('project_tasks')->where('dialog_id', $this->id)->value('name');
                }
            }
            $this->appendattrs['groupName'] = $name;
        }
        return $this->appendattrs['groupName'];
    }

    /**
     * 推送消息
     * @param $action
     * @param array $data           发送内容，默认为[id=>项目ID]
     * @param array $userid         指定会员，默认为群组所有成员
     * @return void
     */
    public function pushMsg($action, $data = null, $userid = null)
    {
        if ($data === null) {
            $data = ['id' => $this->id];
        }
        //
        if ($userid === null) {
            $userid = $this->dialogUser->pluck('userid')->toArray();
        }
        //
        $params = [
            'userid' => $userid,
            'msg' => [
                'type' => 'dialog',
                'mode' => $action,
                'data' => $data,
            ]
        ];
        $task = new PushTask($params, false);
        Task::deliver($task);
    }

    /**
     * 获取对话（同时检验对话身份）
     * @param $dialog_id
     * @param bool|string $checkOwner 是否校验群组身份，'auto'时有群主为true无群主为false
     * @return self
     */
    public static function checkDialog($dialog_id, $checkOwner = false)
    {
        $dialog = WebSocketDialog::find($dialog_id);
        if (empty($dialog)) {
            throw new ApiException('对话不存在或已被删除', ['dialog_id' => $dialog_id], -4003);
        }
        //
        $userid = User::userid();
        if ($checkOwner === 'auto') {
            $checkOwner = $dialog->owner_id > 0;
        }
        if ($checkOwner === true && $dialog->owner_id != $userid) {
            throw new ApiException('仅限群主操作');
        }
        //
        if ($dialog->group_type === 'task') {
            // 任务群对话校验是否在项目内
            $project_id = intval(ProjectTask::whereDialogId($dialog->id)->value('project_id'));
            if ($project_id > 0) {
                if (ProjectUser::whereProjectId($project_id)->whereUserid($userid)->exists()) {
                    return $dialog;
                }
            }
        }
        if (!WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($userid)->exists()) {
            throw new ApiException('不在成员列表内', ['dialog_id' => $dialog_id], -4003);
        }
        return $dialog;
    }

    /**
     * 创建聊天室
     * @param string $name          聊天室名称
     * @param int|array $userid     加入的会员ID(组)
     * @param string $group_type    聊天室类型
     * @param int $owner_id         群主会员ID
     * @return self|null
     */
    public static function createGroup($name, $userid, $group_type = '', $owner_id = 0)
    {
        return AbstractModel::transaction(function () use ($owner_id, $userid, $group_type, $name) {
            $dialog = self::createInstance([
                'type' => 'group',
                'name' => $name ?: '',
                'group_type' => $group_type,
                'owner_id' => $owner_id,
                'last_at' => $group_type === 'user' ? Carbon::now() : null,
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
     * 获取会员对话（没有自动创建）
     * @param int $userid   会员ID
     * @param int $userid2  另一个会员ID
     * @return self|null
     */
    public static function checkUserDialog($userid, $userid2)
    {
        if ($userid == $userid2) {
            $userid2 = 0;
        }
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
