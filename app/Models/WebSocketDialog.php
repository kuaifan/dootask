<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;
use App\Tasks\PushTask;
use Cache;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\WebSocketDialog
 *
 * @property int $id
 * @property string|null $type 对话类型
 * @property string|null $group_type 聊天室类型
 * @property string|null $name 对话名称
 * @property string $avatar 头像（群）
 * @property int|null $owner_id 群主用户ID
 * @property int|null $link_id 关联id
 * @property int|null $top_userid 置顶的用户ID
 * @property int|null $top_msg_id 置顶的消息ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WebSocketDialogUser> $dialogUser
 * @property-read int|null $dialog_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereLinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereTopMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialog whereTopUserid($value)
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
    public static function getDialogList($userid, $updated = "", $deleted = "")
    {
        $builder = DB::table('web_socket_dialog_users as u')
            ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
            ->where('u.userid', $userid)
            ->whereNull('d.deleted_at');
        if ($updated) {
            $builder->where('u.updated_at', '>', $updated);
        }
        $list = $builder
            ->orderByDesc('u.top_at')
            ->orderByDesc('u.last_at')
            ->paginate(Base::getPaginate(100, 50));
        $list->transform(function ($item) use ($userid) {
            return self::synthesizeData($item, $userid);
        });
        //
        $data = $list->toArray();
        if ($list->currentPage() === 1) {
            $data['deleted_id'] = Deleted::ids('dialog', $userid, $deleted);
        }
        return $data;
    }

    /**
     * 列表外的未读对话 和 列表外的待办对话
     * @param $userid
     * @param $unreadAt
     * @param $todoAt
     * @return WebSocketDialog[]
     */
    public static function getDialogBeyond($userid, $unreadAt, $todoAt)
    {
        DB::statement("SET SQL_MODE=''");
        $ids = [];
        $array = [];
        if ($unreadAt) {
            // 未读对话
            $list = DB::table('web_socket_dialog_users as u')
                ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
                ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
                ->join('web_socket_dialog_msg_reads as r', 'd.id', '=', 'r.dialog_id')
                ->where('u.userid', $userid)
                ->where('u.last_at', '<', $unreadAt)
                ->whereNull('d.deleted_at')
                ->where('r.userid', $userid)
                ->where('r.read_at')
                ->groupBy('u.dialog_id')
                ->take(20)
                ->get();
            $list->transform(function ($item) use ($userid, &$ids, &$array) {
                if (!in_array($item->id, $ids)) {
                    $ids[] = $item->id;
                    $array[] = self::synthesizeData($item, $userid);
                }
            });
            // 标记未读会话
            $list = DB::table('web_socket_dialog_users as u')
                ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
                ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
                ->where('u.userid', $userid)
                ->where('u.mark_unread', 1)
                ->where('u.last_at', '<', $unreadAt)
                ->whereNull('d.deleted_at')
                ->take(20)
                ->get();
            $list->transform(function ($item) use ($userid, &$ids, &$array) {
                if (!in_array($item->id, $ids)) {
                    $ids[] = $item->id;
                    $array[] = self::synthesizeData($item, $userid);
                }
            });
        }
        if ($todoAt) {
            // 待办会话
            $list = DB::table('web_socket_dialog_users as u')
                ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
                ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
                ->join('web_socket_dialog_msg_todos as t', 'd.id', '=', 't.dialog_id')
                ->where('u.userid', $userid)
                ->where('u.last_at', '<', $todoAt)
                ->whereNull('d.deleted_at')
                ->where('t.userid', $userid)
                ->where('t.done_at')
                ->groupBy('u.dialog_id')
                ->take(20)
                ->get();
            $list->transform(function ($item) use ($userid, &$ids, &$array) {
                if (!in_array($item->id, $ids)) {
                    $ids[] = $item->id;
                    $array[] = self::synthesizeData($item, $userid);
                }
            });
        }
        return $array;
    }

    /**
     * 综合数据
     * @param $data
     * @param int $userid   会员ID
     * @param bool $hasData 已存在的消息类型
     * @return array
     */
    public static function synthesizeData($data, $userid, $hasData = false)
    {
        // 判断数据
        if (is_numeric($data)) {
            $data = WebSocketDialog::find($data)?->toArray();
        } elseif ($data instanceof Model) {
            $data = $data->toArray();
        } elseif (is_object($data)) {
            $data = (array)$data;
        }
        if (!is_array($data) || !isset($data['id'])) {
            return $data;
        }

        // 会话必要字段
        $fields = [
            'id', 'type', 'group_type', 'name', 'avatar', 'owner_id', 'link_id', 'top_userid', 'top_msg_id', 'created_at', 'updated_at', 'deleted_at',
        ];
        if (!empty(array_diff($fields, array_keys($data)))) {
            // 补全数据
            foreach ($fields as $field) {
                $data[$field] = $data[$field] ?? null;
            }
        }
        $data['avatar'] = Base::fillUrl($data['avatar']);

        // 会员必要字段
        $fields = [
            'top_at', 'last_at', 'mark_unread', 'silence', 'hide', 'color', 'user_at',
        ];
        if (!empty(array_diff($fields, array_keys($data)))) {
            // 补全数据（查询数据库）
            $array = WebSocketDialogUser::whereDialogId($data['id'])->whereUserid($userid)->first()?->toArray();
            foreach ($fields as $field) {
                if ($field === 'user_at') {
                    $data[$field] = $data[$field] ?? $array['updated_at'] ?? null;
                } else {
                    $data[$field] = $data[$field] ?? $array[$field] ?? null;
                }
            }
        }
        // 会员数据处理
        if (isset($data['user_at']) && !isset($data['user_ms'])) {
            $time = Carbon::parse($data['user_at']);
            $data['user_at'] = $time->toDateTimeString('millisecond');
            $data['user_ms'] = $time->valueOf();
        }

        // 信息数据
        if (isset($data['search_msg_id'])) {
            // 最后消息 (搜索预览消息)
            $data['last_msg'] = $data['last_msg'] ?? WebSocketDialogMsg::whereDialogId($data['id'])->find($data['search_msg_id'])?->toArray();
            $data['last_at'] = $data['last_msg'] ? Carbon::parse($data['last_msg']['created_at'])->toDateTimeString() : null;
        } else {
            // 未读消息
            $data = array_merge($data, self::generateUnread($data['id'], $userid));
            // 对话人数
            $data['people'] = $data['people'] ?? WebSocketDialogUser::whereDialogId($data['id'])->count();
            // 有待办
            $data['todo_num'] = $data['todo_num'] ?? WebSocketDialogMsgTodo::whereDialogId($data['id'])->whereUserid($userid)->whereDoneAt(null)->count();
            // 最后消息
            $data['last_msg'] = $data['last_msg'] ?? WebSocketDialogMsg::whereDialogId($data['id'])->orderByDesc('id')->first()?->toArray();
        }
        $data['last_msg'] = self::lastMsgFormat($data['last_msg']);

        // 对方信息
        $data['pinyin'] = Base::cn2pinyin($data['name']);
        $data['quick_msgs'] = [];
        $data['dialog_user'] = null;
        $data['group_info'] = null;
        $data['bot'] = 0;
        switch ($data['type']) {
            case "user":
                $dialog_user = WebSocketDialogUser::whereDialogId($data['id'])->where('userid', '!=', $userid)->first();
                if ($dialog_user->userid === 0) {
                    $dialog_user->userid = $userid;
                }
                $basic = User::userid2basic($dialog_user->userid);
                if ($basic) {
                    $data['name'] = $basic->nickname;
                    $data['email'] = $basic->email;
                    $data['userimg'] = $basic->userimg;
                    $data['bot'] = $basic->getBotOwner();
                    $data['quick_msgs'] = UserBot::quickMsgs($basic->email);
                } else {
                    $data['name'] = 'non-existent';
                    $data['dialog_delete'] = 1;
                }
                $data['dialog_user'] = $dialog_user;
                $data['dialog_mute'] = Base::settingFind('system', 'user_private_chat_mute');
                break;
            case "group":
                switch ($data['group_type']) {
                    case 'user':
                        $data['dialog_mute'] = Base::settingFind('system', 'user_group_chat_mute');
                        break;
                    case 'project':
                        $data['group_info'] = Project::withTrashed()->select(['id', 'name', 'archived_at', 'deleted_at'])->whereDialogId($data['id'])->first()?->cancelAppend()->cancelHidden()->toArray();
                        if ($data['group_info']) {
                            $data['name'] = $data['group_info']['name'];
                        } else {
                            $data['name'] = '[Delete]';
                            $data['dialog_delete'] = 1;
                        }
                        break;
                    case 'task':
                        $data['group_info'] = ProjectTask::withTrashed()->select(['id', 'name', 'complete_at', 'archived_at', 'deleted_at'])->whereDialogId($data['id'])->first()?->cancelAppend()->cancelHidden()->toArray();
                        if ($data['group_info']) {
                            $data['name'] = $data['group_info']['name'];
                        } else {
                            $data['name'] = '[Delete]';
                            $data['dialog_delete'] = 1;
                        }
                        break;
                    case 'all':
                        $data['name'] = Doo::translate('全体成员');
                        $data['dialog_mute'] = Base::settingFind('system', 'all_group_mute');
                        break;
                }
                break;
        }

        // 已存在的消息类型
        if ($hasData === true) {
            $msgBuilder = WebSocketDialogMsg::whereDialogId($data['id']);
            $data['has_tag'] = $msgBuilder->clone()->where('tag', '>', 0)->exists();
            $data['has_todo'] = $msgBuilder->clone()->where('todo', '>', 0)->exists();
            $data['has_image'] = $msgBuilder->clone()->whereMtype('image')->exists();
            $data['has_file'] = $msgBuilder->clone()->whereMtype('file')->exists();
            $data['has_link'] = $msgBuilder->clone()->whereLink(1)->exists();
            Cache::forever("Dialog::tag:" . $data['id'], Base::array2json([
                'has_tag' => $data['has_tag'],
                'has_todo' => $data['has_todo'],
                'has_image' => $data['has_image'],
                'has_file' => $data['has_file'],
                'has_link' => $data['has_link'],
            ]));
        } else {
            $tagData = Base::json2array(Cache::get("Dialog::tag:" . $data['id']));
            if ($tagData) {
                $data['has_tag'] = !!$tagData['has_tag'];
                $data['has_todo'] = !!$tagData['has_todo'];
                $data['has_image'] = !!$tagData['has_image'];
                $data['has_file'] = !!$tagData['has_file'];
                $data['has_link'] = !!$tagData['has_link'];
            }
        }
        return $data;
    }

    /**
     * 格式化最后消息
     * @param array $lastMsg
     * @return array
     */
    public static function lastMsgFormat($lastMsg)
    {
        if ($lastMsg && $lastMsg['type'] != 'preview') {
            $msgData = $lastMsg;
            $msgData['emoji'] = Base::array_only_recursive($msgData['emoji'], ['symbol']);
            $msgData['msg'] = ['preview' => WebSocketDialogMsg::previewMsg($msgData)];
            $msgData['type'] = 'preview';
            $lastMsg = array_intersect_key($msgData, array_flip(['id', 'type', 'msg', 'userid', 'percentage', 'emoji', 'created_at']));
        }
        return $lastMsg;
    }

    /**
     * 获取未读数据
     * @param $dialogId
     * @param $userid
     * @return array
     */
    public static function generateUnread($dialogId, $userid)
    {
        $data = [];
        // 未读消息
        $builder = WebSocketDialogMsgRead::whereDialogId($dialogId)->whereUserid($userid)->whereReadAt(null);
        // 总未读消息
        $data['unread'] = $builder->clone()->count();
        // 最早一条未读消息
        $data['unread_one'] = $data['unread'] > 0 ? intval($builder->clone()->orderBy('msg_id')->value('msg_id')) : 0;
        // @我的消息
        $data['mention'] = $data['unread'] > 0 ? $builder->clone()->whereMention(1)->count() : 0;
        // @我的消息（id集合）
        $data['mention_ids'] = $data['mention'] > 0 ? $builder->clone()->whereMention(1)->orderByDesc('msg_id')->take(20)->pluck('msg_id')->toArray() : [];
        return $data;
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
        $muteMsgTip = null;
        $systemConfig = Base::setting('system');
        switch ($this->type) {
            case 'user':
                if ($systemConfig['user_private_chat_mute'] === 'close') {
                    $muteMsgTip = '个人会话禁言';
                }
                break;

            case 'group':
                if ($this->group_type === 'user') {
                    if ($systemConfig['user_group_chat_mute'] === 'close') {
                        $muteMsgTip = '个人群组禁言';
                    }
                } elseif ($this->group_type === 'all') {
                    if ($systemConfig['all_group_mute'] === 'close') {
                        $muteMsgTip = '当前会话全员禁言';
                    }
                }
                break;
        }
        if ($muteMsgTip === null) {
            return;
        }
        if ($userid) {
            $user = User::find($userid);
            if ($user?->bot || $user?->isAdmin()) { // 机器人或管理员不受禁言
                return;
            }
        }
        throw new ApiException($muteMsgTip);
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
            WebSocketDialogMsgRead::forceRead($dialog_id, $userid);
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
            ]);
            $dialog->save();
            foreach (is_array($userid) ? $userid : [$userid] as $value) {
                if ($value > 0) {
                    WebSocketDialogUser::createInstance([
                        'dialog_id' => $dialog->id,
                        'userid' => $value,
                        'important' => !in_array($group_type, ['user', 'all']),
                        'last_at' => in_array($group_type, ['user', 'department', 'all']) ? Carbon::now() : null,
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
     * @return WebSocketDialog|null
     */
    public static function checkUserDialog($user, $receiver)
    {
        if ($user->userid == $receiver) {
            $receiver = 0;
        }
        $dialogUser = self::getUserDialog($user->userid, $receiver, 0, $cacheKey);
        if ($dialogUser) {
            return $dialogUser;
        }
        if ($receiver > 0 && $user->isTemp() && !User::whereUserid($receiver)->whereBot(1)->exists() ) {
            throw new ApiException('无法发起会话，请联系管理员。');
        }
        return AbstractModel::transaction(function () use ($cacheKey, $receiver, $user) {
            Cache::forget($cacheKey);
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
     * 获取用户对话（支持缓存）
     * @param $userid1
     * @param $userid2
     * @param $ttl
     * @param null $cacheKey
     * @return \Illuminate\Database\Eloquent\Builder|WebSocketDialog|null
     */
    public static function getUserDialog($userid1, $userid2, $ttl, &$cacheKey = null)
    {
        $userids = [$userid1, $userid2];
        sort($userids);
        $cacheKey = "Dialog::user:" . implode('-', $userids);
        if (empty($ttl)) {
            return WebSocketDialog::query()
                ->whereType('user')
                ->whereExists(function ($query) use ($userids) {
                    $query->select(DB::raw(1))
                        ->from('web_socket_dialog_users')
                        ->whereColumn('web_socket_dialog_users.dialog_id', 'web_socket_dialogs.id')
                        ->where('web_socket_dialog_users.userid', $userids[0]);
                })
                ->whereExists(function ($query) use ($userids) {
                    $query->select(DB::raw(1))
                        ->from('web_socket_dialog_users')
                        ->whereColumn('web_socket_dialog_users.dialog_id', 'web_socket_dialogs.id')
                        ->where('web_socket_dialog_users.userid', $userids[1]);
                })
                ->first();
        }
        return Cache::remember($cacheKey, $ttl, function() use ($userids) {
            return self::getUserDialog($userids[0], $userids[1], 0);
        });
    }

    /**
     * 发送消息文件
     *
     * @param User $user 发起会话的会员
     * @param array $dialogIds 对话id
     * @param file|mixed $files 文件对象
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
        $data = [];
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
                    "quality" => 85
                ]);
            } else if ($filePath) {
                Base::makeDir(public_path($path));
                copy($filePath, public_path($path) . basename($filePath));
            } else {
                $data = Base::upload([
                    "file" => $files,
                    "type" => 'more',
                    "path" => $path,
                    "fileName" => $fileName,
                    "quality" => 100,
                    "convertVideo" => true
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
