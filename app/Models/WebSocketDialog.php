<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;
use App\Tasks\PushTask;
use Cache;
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
 * @property string $avatar 头像（群）
 * @property string|null $last_at 最后消息时间
 * @property int|null $owner_id 群主用户ID
 * @property int|null $link_id 关联id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WebSocketDialogUser> $dialogUser
 * @property-read int|null $dialog_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereLinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog withoutTrashed()
 * @mixin \Eloquent
 */
class WebSocketDialog extends AbstractModel
{
    use SoftDeletes;

    /**
     * 头像地址
     * @param $value
     * @return string
     */
    public function getAvatarAttribute($value)
    {
        return $value ? Base::fillUrl($value) : $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dialogUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebSocketDialogUser::class, 'dialog_id', 'id');
    }


    /**
     * 获取对话列表
     * @param int $userid 会员ID
     * @param $updated
     * @param $deleted
     * @return array
     */
    public function getDialogList($userid, $updated = "", $deleted = "")
    {
        $builder = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread', 'u.silence', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('u.userid', $userid);
        if ($updated) {
            $builder->where('u.updated_at', '>', $updated);
        }
        $list = $builder
            ->orderByDesc('u.top_at')
            ->orderByDesc('web_socket_dialogs.last_at')
            ->paginate(Base::getPaginate(100, 50));
        $list->transform(function (WebSocketDialog $item) use ($userid) {
            return $item->formatData($userid);
        });
        //
        $data = $list->toArray();
        if ($list->currentPage() === 1) {
            $data['deleted_id'] = Deleted::ids('dialog', $userid, $deleted);
        }
        return $data;
    }

    /**
     * 格式化对话
     * @param int $userid   会员ID
     * @param bool $hasData
     * @return $this
     */
    public function formatData($userid, $hasData = false)
    {
        $dialogUserFun = function ($key, $default = null) use ($userid) {
            $data = Cache::remember("Dialog::formatData-{$this->id}-{$userid}", now()->addSeconds(10), function () use ($userid) {
                return WebSocketDialogUser::whereDialogId($this->id)->whereUserid($userid)->first()?->toArray();
            });
            return $data[$key] ?? $default;
        };
        //
        $time = Carbon::parse($this->user_at ?? $dialogUserFun('updated_at'));
        $this->top_at = $this->top_at ?? $dialogUserFun('top_at');
        $this->user_at = $time->toDateTimeString('millisecond');
        $this->user_ms = $time->valueOf();
        //
        if (isset($this->search_msg_id)) {
            // 最后消息 (搜索预览消息)
            $this->last_msg = WebSocketDialogMsg::whereDialogId($this->id)->find($this->search_msg_id);
            $this->last_at = $this->last_msg?->created_at;
        } else {
            // 未读信息
            $this->generateUnread($userid, $hasData);
            // 未读标记
            $this->mark_unread = $this->mark_unread ?? $dialogUserFun('mark_unread');
            // 是否免打扰
            $this->silence = $this->silence ?? $dialogUserFun('silence');
            // 对话人数
            $this->people = WebSocketDialogUser::whereDialogId($this->id)->count();
            // 有待办
            $this->todo_num = WebSocketDialogMsgTodo::whereDialogId($this->id)->whereUserid($userid)->whereDoneAt(null)->count();
            // 最后消息
            $this->last_msg = WebSocketDialogMsg::whereDialogId($this->id)->orderByDesc('id')->first();
        }
        // 对方信息
        $this->pinyin = Base::cn2pinyin($this->name);
        $this->quick_msgs = [];
        $this->dialog_user = null;
        $this->group_info = null;
        $this->bot = 0;
        switch ($this->type) {
            case "user":
                $dialog_user = WebSocketDialogUser::whereDialogId($this->id)->where('userid', '!=', $userid)->first();
                if ($dialog_user->userid === 0) {
                    $dialog_user->userid = $userid;
                }
                $basic = User::userid2basic($dialog_user->userid);
                if ($basic) {
                    $this->name = $basic->nickname;
                    $this->email = $basic->email;
                    $this->userimg = $basic->userimg;
                    $this->bot = $basic->getBotOwner();
                    $this->quick_msgs = UserBot::quickMsgs($basic->email);
                } else {
                    $this->name = 'non-existent';
                    $this->dialog_delete = 1;
                }
                $this->dialog_user = $dialog_user;
                break;
            case "group":
                switch ($this->group_type) {
                    case 'project':
                        $this->group_info = Project::withTrashed()->select(['id', 'name', 'archived_at', 'deleted_at'])->whereDialogId($this->id)->first()?->cancelAppend()->cancelHidden();
                        if ($this->group_info) {
                            $this->name = $this->group_info->name;
                        } else {
                            $this->name = '[Delete]';
                            $this->dialog_delete = 1;
                        }
                        break;
                    case 'task':
                        $this->group_info = ProjectTask::withTrashed()->select(['id', 'name', 'complete_at', 'archived_at', 'deleted_at'])->whereDialogId($this->id)->first()?->cancelAppend()->cancelHidden();
                        if ($this->group_info) {
                            $this->name = $this->group_info->name;
                        } else {
                            $this->name = '[Delete]';
                            $this->dialog_delete = 1;
                        }
                        break;
                    case 'all':
                        $this->name = Doo::translate('全体成员');
                        $this->all_group_mute = Base::settingFind('system', 'all_group_mute');
                        break;
                }
                break;
        }
        if ($hasData === true) {
            $msgBuilder = WebSocketDialogMsg::whereDialogId($this->id);
            $this->has_tag = $msgBuilder->clone()->where('tag', '>', 0)->exists();
            $this->has_image = $msgBuilder->clone()->whereMtype('image')->exists();
            $this->has_file = $msgBuilder->clone()->whereMtype('file')->exists();
            $this->has_link = $msgBuilder->clone()->whereLink(1)->exists();
            $this->has_todo = $msgBuilder->clone()->where('todo', '>', 0)->exists();
        }
        return $this;
    }

    /**
     * 生成未读数据
     * @param $userid
     * @param $positionData
     * @return $this
     */
    public function generateUnread($userid, $positionData = false)
    {
        $builder = WebSocketDialogMsgRead::whereDialogId($this->id)->whereUserid($userid)->whereReadAt(null);
        $this->unread = $builder->count();
        $this->mention = $this->unread > 0 ? $builder->clone()->whereMention(1)->count() : 0;
        if ($positionData) {
            $array = [];
            // @我的消息
            if ($this->mention > 0) {
                $list = $builder->clone()->whereMention(1)->orderByDesc('msg_id')->take(20)->get();
                foreach ($list as $item) {
                    $array[] = [
                        'msg_id' => $item->msg_id,
                        'label' => Doo::translate('@我的消息'),
                    ];
                }
            }
            // 最早一条未读消息
            if ($this->unread > 0
                && $first_id = intval($builder->clone()->orderBy('msg_id')->value('msg_id'))) {
                $array[] = [
                    'msg_id' => $first_id,
                    'label' => '{UNREAD}'
                ];
            }
            //
            $this->position_msgs = $array;
        }
        return $this;
    }

    /**
     * 加入聊天室
     * @param int|array $userid         加入的会员ID或会员ID组
     * @param int $inviter              邀请人
     * @param bool|null $important      重要人员(null不修改、bool修改)
     * @return bool
     */
    public function joinGroup($userid, $inviter, $important = null)
    {
        AbstractModel::transaction(function () use ($important, $inviter, $userid) {
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    $updateData = [
                        'inviter' => $inviter,
                    ];
                    if (is_bool($important)) {
                        $updateData['important'] = $important ? 1 : 0;
                    }
                    $isInsert = false;
                    WebSocketDialogUser::updateInsert([
                        'dialog_id' => $this->id,
                        'userid' => $value,
                    ], $updateData, [], $isInsert);
                    if ($isInsert) {
                        WebSocketDialogMsg::sendMsg(null, $this->id, 'notice', [
                            'notice' => User::userid2nickname($value) . " 已加入群组"
                        ], $inviter, true, true);
                    }
                }
            }
        });
        $this->pushMsg("groupUpdate", [
            'id' => $this->id,
            'people' => WebSocketDialogUser::whereDialogId($this->id)->count()
        ]);
        return true;
    }

    /**
     * 退出聊天室
     * @param int|array $userid     退出的会员ID或会员ID组
     * @param string $type          exit|remove
     * @param bool $checkDelete     是否检查删除
     * @param bool $pushMsg         是否推送消息
     */
    public function exitGroup($userid, $type = 'exit', $checkDelete = true, $pushMsg = true)
    {
        $typeDesc = $type === 'remove' ? '移出' : '退出';
        AbstractModel::transaction(function () use ($pushMsg, $checkDelete, $typeDesc, $type, $userid) {
            $builder = WebSocketDialogUser::whereDialogId($this->id);
            if (is_array($userid)) {
                $builder->whereIn('userid', $userid);
            } else {
                $builder->whereUserid($userid);
            }
            $builder->chunkById(100, function($list) use ($pushMsg, $checkDelete, $typeDesc, $type) {
                /** @var WebSocketDialogUser $item */
                foreach ($list as $item) {
                    if ($checkDelete) {
                        if ($type === 'remove') {
                            // 移出时：如果是全员群仅允许管理员操作，其他群仅群主或邀请人可以操作
                            if ($this->group_type === 'all') {
                                User::auth("admin");
                            } elseif (!in_array(User::userid(), [$this->owner_id, $item->inviter])) {
                                throw new ApiException('只有群主或邀请人可以移出成员');
                            }
                        }
                        if ($item->userid == $this->owner_id) {
                            throw new ApiException('群主不可' . $typeDesc);
                        }
                        if ($item->important) {
                            throw new ApiException('部门成员、项目人员或任务人员不可' . $typeDesc);
                        }
                    }
                    //
                    $item->delete();
                    //
                    if ($pushMsg) {
                        if ($type === 'remove') {
                            $notice = User::nickname() . " 将 " . User::userid2nickname($item->userid) . " 移出群组";
                        } else {
                            $notice = User::userid2nickname($item->userid) . " 退出群组";
                        }
                        WebSocketDialogMsg::sendMsg(null, $this->id, 'notice', [
                            'notice' => $notice
                        ], User::userid(), true, true);
                    }
                }
            });
        });
        //
        $this->pushMsg("groupUpdate", [
            'id' => $this->id,
            'people' => WebSocketDialogUser::whereDialogId($this->id)->count()
        ]);
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
        $this->pushMsg("groupDelete");
        return true;
    }

    /**
     * 还原会话
     * @return bool
     */
    public function restoreDialog()
    {
        $this->restore();
        $this->pushMsg("groupRestore");
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
     * 检查禁言
     * @param $userid
     * @return void
     */
    public function checkMute($userid)
    {
        if ($this->group_type === 'all') {
            $allGroupMute = Base::settingFind('system', 'all_group_mute');
            switch ($allGroupMute) {
                case 'all':
                    throw new ApiException('当前会话全员禁言');
                case 'user':
                    if (!User::find($userid)?->isAdmin()) {
                        throw new ApiException('当前会话禁言');
                    }
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
                switch ($this->group_type) {
                    case 'project':
                        $name = \DB::table('projects')->where('dialog_id', $this->id)->value('name');
                        break;
                    case 'task':
                        $name = \DB::table('project_tasks')->where('dialog_id', $this->id)->value('name');
                        break;
                    case 'all':
                        $name = Doo::translate('全体成员');
                        break;
                }
            }
            $this->appendattrs['groupName'] = $name;
        }
        return $this->appendattrs['groupName'];
    }

    /**
     * 推送消息
     * @param $action
     * @param array $data           发送内容，默认为[id=>会话ID]
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
     * 更新对话最后消息时间
     * @return WebSocketDialogMsg|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function updateMsgLastAt()
    {
        $lastMsg = WebSocketDialogMsg::whereDialogId($this->id)->orderByDesc('id')->first();
        if ($lastMsg) {
            $this->last_at = $lastMsg->created_at;
            $this->save();
        }
        return $lastMsg;
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
        if ($dialog->group_type == 'okr') {
            return $dialog;
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
                'last_at' => in_array($group_type, ['user', 'department', 'all']) ? Carbon::now() : null,
            ]);
            $dialog->save();
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    WebSocketDialogUser::createInstance([
                        'dialog_id' => $dialog->id,
                        'userid' => $value,
                        'important' => !in_array($group_type, ['user', 'all'])
                    ])->save();
                }
            }
            return $dialog;
        });
    }

    /**
     * 获取会员对话（没有自动创建）
     * @param User $user    发起会话的会员
     * @param int $receiver  另一个会员ID
     * @return self|null
     */
    public static function checkUserDialog($user, $receiver)
    {
        if ($user->userid == $receiver) {
            $receiver = 0;
        }
        $dialogUser = self::select(['web_socket_dialogs.*'])
            ->join('web_socket_dialog_users as u1', 'web_socket_dialogs.id', '=', 'u1.dialog_id')
            ->join('web_socket_dialog_users as u2', 'web_socket_dialogs.id', '=', 'u2.dialog_id')
            ->where('u1.userid', $user->userid)
            ->where('u2.userid', $receiver)
            ->where('web_socket_dialogs.type', 'user')
            ->first();
        if ($dialogUser) {
            return $dialogUser;
        }
        if ($receiver > 0 && $user->isTemp() && !User::whereUserid($receiver)->whereBot(1)->exists() ) {
            throw new ApiException('无法发起会话，请联系管理员。');
        }
        return AbstractModel::transaction(function () use ($receiver, $user) {
            $dialog = self::createInstance([
                'type' => 'user',
            ]);
            $dialog->save();
            WebSocketDialogUser::createInstance([
                'dialog_id' => $dialog->id,
                'userid' => $user->userid,
            ])->save();
            WebSocketDialogUser::createInstance([
                'dialog_id' => $dialog->id,
                'userid' => $receiver,
            ])->save();
            return $dialog;
        });
    }

    /**
     * 发送消息文件
     *
     * @param User $user 发起会话的会员
     * @param array $dialogIds 对话id
     * @param file $files 文件对象
     * @param string $image64 base64文件
     * @param string $fileName 文件名称
     * @param int $replyId 恢复id
     * @param int $imageAttachment
     * @return array
     */
    public static function sendMsgFiles($user, $dialogIds, $files, $image64, $fileName, $replyId, $imageAttachment)
    {
        $filePath = '';
        $result = [];
        foreach ($dialogIds as $dialog_id) {
            $dialog = WebSocketDialog::checkDialog($dialog_id);
            //
            $action = $replyId > 0 ? "reply-$replyId" : "";
            $path = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
            if ($image64) {
                $data = Base::image64save([
                    "image64" => $image64,
                    "path" => $path,
                    "fileName" => $fileName,
                ]);
            } else if ($filePath) {
                Base::makeDir(public_path($path));
                copy($filePath, public_path($path) . basename($filePath));
            } else {
                $setting = Base::setting('system');
                $data = Base::upload([
                    "file" => $files,
                    "type" => 'more',
                    "path" => $path,
                    "fileName" => $fileName,
                    "size" => ($setting['file_upload_limit'] ?: 0) * 1024
                ]);
            }
            //
            if (Base::isError($data)) {
                throw new ApiException($data['msg']);
            } else {
                $fileData = $data['data'];
                $filePath = $fileData['file'];
                $fileName = $fileData['name'];
                $fileData['thumb'] = Base::unFillUrl($fileData['thumb']);
                $fileData['size'] *= 1024;
                //
                if ($dialog->type === 'group' && $dialog->group_type === 'task') {                // 任务群组保存文件
                    if ($imageAttachment || !in_array($fileData['ext'], File::imageExt)) {         // 如果是图片不保存
                        $task = ProjectTask::whereDialogId($dialog->id)->first();
                        if ($task) {
                            $file = ProjectTaskFile::createInstance([
                                'project_id' => $task->project_id,
                                'task_id' => $task->id,
                                'name' => $fileData['name'],
                                'size' => $fileData['size'],
                                'ext' => $fileData['ext'],
                                'path' => $fileData['path'],
                                'thumb' => $fileData['thumb'],
                                'userid' => $user->userid,
                            ]);
                            $file->save();
                        }
                    }
                }
                //
                $result = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'file', $fileData, $user->userid);
                if (Base::isSuccess($result)) {
                    if (isset($task)) {
                        $result['data']['task_id'] = $task->id;
                    }
                }
            }
        }
        return $result;
    }
}
