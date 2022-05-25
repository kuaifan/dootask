<?php

namespace App\Http\Controllers\Api;

use App\Models\File;
use App\Models\ProjectTask;
use App\Models\ProjectTaskFile;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Models\WebSocketDialogUser;
use App\Module\Base;
use Carbon\Carbon;
use DB;
use Request;
use Response;

/**
 * @apiDefine dialog
 *
 * 对话
 */
class DialogController extends AbstractController
{
    /**
     * @api {get} api/dialog/lists          01. 对话列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName lists
     *
     * @apiParam {String} [at_after]        只读取在这个时间之后更新的对话
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:100，最大:200
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function lists()
    {
        $user = User::auth();
        //
        $builder = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('u.userid', $user->userid);
        if (Request::exists('at_after')) {
            $builder->where('web_socket_dialogs.last_at', '>', Carbon::parse(Request::input('at_after')));
        }
        $list = $builder
            ->orderByDesc('u.top_at')
            ->orderByDesc('web_socket_dialogs.last_at')
            ->paginate(Base::getPaginate(200, 100));
        $list->transform(function (WebSocketDialog $item) use ($user) {
            return $item->formatData($user->userid);
        });
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/dialog/one          02. 获取单个会话信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName one
     *
     * @apiParam {Number} dialog_id         对话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function one()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $item = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('web_socket_dialogs.id', $dialog_id)
            ->where('u.userid', $user->userid)
            ->first();
        if ($item) {
            $item = $item->formatData($user->userid);
        }
        //
        return Base::retSuccess('success', $item);
    }

    /**
     * @api {get} api/dialog/msg/user          03. 打开会话
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName open__user
     *
     * @apiParam {Number} userid         对话会员ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function open__user()
    {
        $user = User::auth();
        //
        $userid = intval(Request::input('userid'));
        if (empty($userid)) {
            return Base::retError('错误的会话');
        }
        //
        $dialog = WebSocketDialog::checkUserDialog($user->userid, $userid);
        if (empty($dialog)) {
            return Base::retError('打开会话失败');
        }
        $data = WebSocketDialog::find($dialog->id)?->formatData($user->userid);
        if (empty($data)) {
            return Base::retError('打开会话错误');
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/lists          04. 获取消息列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__lists
     *
     * @apiParam {Number} dialog_id         对话ID
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:50，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__lists()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        $list = WebSocketDialogMsg::select([
            'web_socket_dialog_msgs.*',
            'read.mention',
            'read.read_at',
        ])->leftJoin('web_socket_dialog_msg_reads as read', function ($leftJoin) use ($user) {
            $leftJoin
                ->on('read.userid', '=', DB::raw($user->userid))
                ->on('read.msg_id', '=', 'web_socket_dialog_msgs.id');
        })->where('web_socket_dialog_msgs.dialog_id', $dialog_id)->orderByDesc('web_socket_dialog_msgs.id')->paginate(Base::getPaginate(100, 50));
        //
        if ($dialog->type == 'group' && $dialog->group_type == 'task') {
            $user->task_dialog_id = $dialog->id;
            $user->save();
        }
        //去掉标记未读
        $isMarkDialogUser = WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($user->userid)->whereMarkUnread(1)->first();
        if ($isMarkDialogUser) {
            $isMarkDialogUser->mark_unread = 0;
            $isMarkDialogUser->save();
        }
        //
        $data = $list->toArray();
        if ($list->currentPage() === 1) {
            $data['dialog'] = $dialog->formatData($user->userid);
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/unread          05. 获取未读消息数量
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__unread
     *
     * @apiParam {Number} [dialog_id]         对话ID，留空获取总未读消息数量
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__unread()
    {
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $builder = WebSocketDialogMsgRead::whereUserid(User::userid())->whereReadAt(null);
        if ($dialog_id > 0) {
            $builder->whereDialogId($dialog_id);
        }
        $unread = $builder->count();
        return Base::retSuccess('success', [
            'unread' => $unread,
        ]);
    }

    /**
     * @api {post} api/dialog/msg/sendtext          06. 发送消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendtext
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {String} text              消息内容
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendtext()
    {
        Base::checkClientVersion('0.13.33');
        $user = User::auth();
        //
        $chat_nickname = Base::settingFind('system', 'chat_nickname');
        if ($chat_nickname == 'required') {
            $nickname = User::select(['nickname as nickname_original'])->whereUserid($user->userid)->value('nickname_original');
            if (empty($nickname)) {
                return Base::retError('请设置昵称', [], -2);
            }
        }
        //
        $dialog_id = Base::getPostInt('dialog_id');
        $text = trim(Base::getPostValue('text'));
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        $text = WebSocketDialogMsg::formatMsg($text, $dialog_id);
        if (mb_strlen($text) < 1) {
            return Base::retError('消息内容不能为空');
        } elseif (mb_strlen($text) > 20000) {
            return Base::retError('消息内容最大不能超过20000字');
        }
        if (mb_strlen($text) > 2000) {
            $array = mb_str_split($text, 2000);
        } else {
            $array = [$text];
        }
        //
        $list = [];
        foreach ($array as $item) {
            $res = WebSocketDialogMsg::sendMsg($dialog_id, 'text', ['text' => $item], $user->userid);
            if (Base::isSuccess($res)) {
                $list[] = $res['data'];
            }
        }
        //
        return Base::retSuccess('发送成功', $list);
    }

    /**
     * @api {post} api/dialog/msg/sendfile          07. 文件上传
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendfile
     *
     * @apiParam {Number} dialog_id             对话ID
     * @apiParam {Number} [image_attachment]    图片是否也存到附件
     * @apiParam {String} [filename]            post-文件名称
     * @apiParam {String} [image64]             post-base64图片（二选一）
     * @apiParam {File} [files]                 post-文件对象（二选一）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendfile()
    {
        $user = User::auth();
        //
        $dialog_id = Base::getPostInt('dialog_id');
        $image_attachment = Base::getPostInt('image_attachment');
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        $path = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
        $image64 = Base::getPostValue('image64');
        $fileName = Base::getPostValue('filename');
        if ($image64) {
            $data = Base::image64save([
                "image64" => $image64,
                "path" => $path,
                "fileName" => $fileName,
            ]);
        } else {
            $data = Base::upload([
                "file" => Request::file('files'),
                "type" => 'more',
                "path" => $path,
                "fileName" => $fileName,
            ]);
        }
        //
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            $fileData = $data['data'];
            $fileData['thumb'] = Base::unFillUrl($fileData['thumb']);
            $fileData['size'] *= 1024;
            //
            if ($dialog->type === 'group' && $dialog->group_type === 'task') {                       // 任务群组保存文件
                if ($image_attachment || !in_array($fileData['ext'], File::imageExt)) {     // 如果是图片不保存
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
            $result = WebSocketDialogMsg::sendMsg($dialog_id, 'file', $fileData, $user->userid);
            if (Base::isSuccess($result)) {
                if (isset($task)) {
                    $result['data']['task_id'] = $task->id;
                }
            }
            return $result;
        }
    }

    /**
     * @api {get} api/dialog/msg/readlist          08. 获取消息阅读情况
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__readlist
     *
     * @apiParam {Number} msg_id            消息ID（需要是消息的发送人）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__readlist()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->whereUserid($user->userid)->first();
        if (empty($msg)) {
            return Base::retError('不是发送人');
        }
        //
        $read = WebSocketDialogMsgRead::whereMsgId($msg_id)->get();
        return Base::retSuccess('success', $read ?: []);
    }

    /**
     * @api {get} api/dialog/msg/detail          09. 消息详情
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__detail
     *
     * @apiParam {Number} msg_id            消息ID
     * @apiParam {String} only_update_at    仅获取update_at字段
     * - no (默认)
     * - yes
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__detail()
    {
        User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        $only_update_at = Request::input('only_update_at', 'no');
        //
        $dialogMsg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($dialogMsg)) {
            return Base::retError("文件不存在");
        }
        //
        if ($only_update_at == 'yes') {
            return Base::retSuccess('success', [
                'id' => $dialogMsg->id,
                'update_at' => Carbon::parse($dialogMsg->updated_at)->toDateTimeString()
            ]);
        }
        //
        $data = $dialogMsg->toArray();
        //
        if ($data['type'] == 'file') {
            $msg = Base::json2array($dialogMsg->getRawOriginal('msg'));
            $msg = File::formatFileData($msg);
            $data['content'] = $msg['content'];
            $data['file_mode'] = $msg['file_mode'];
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/download          10. 文件下载
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__download
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__download()
    {
        User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            abort(403, "This file not exist.");
        }
        if ($msg->type != 'file') {
            abort(403, "This file not support download.");
        }
        $array = Base::json2array($msg->getRawOriginal('msg'));
        //
        return Response::download(public_path($array['path']), $array['name']);
    }

    /**
     * @api {get} api/dialog/msg/withdraw          11. 聊天消息撤回
     *
     * @apiDescription 消息撤回限制24小时内，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__withdraw
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__withdraw()
    {
        $user = User::auth();
        $msg_id = intval(Request::input("msg_id"));
        $msg = WebSocketDialogMsg::whereId($msg_id)->whereUserid($user->userid)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        $msg->deleteMsg();
        return Base::retSuccess("success");
    }

    /**
     * @api {get} api/dialog/top          12. 会话置顶
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName top
     *
     * @apiParam {Number} dialog_id            会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function top()
    {
        $user = User::auth();
        $dialogId = intval(Request::input('dialog_id'));
        $dialogUser = WebSocketDialogUser::whereUserid($user->userid)->whereDialogId($dialogId)->first();
        if (!$dialogUser) {
            return Base::retError("会话不存在");
        }
        $dialogUser->top_at = $dialogUser->top_at ? null : Carbon::now();
        $dialogUser->save();
        return Base::retSuccess("success", [
            'id' => $dialogUser->dialog_id,
            'top_at' => $dialogUser->top_at?->toDateTimeString(),
        ]);
    }

    /**
     * @api {get} api/dialog/msg/mark          13. 消息标记操作
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__mark
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {String} type                  类型
     * - read
     * - unread
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__mark()
    {
        $user = User::auth();
        $dialogId = intval(Request::input('dialog_id'));
        $type = Request::input('type');
        $dialogUser = WebSocketDialogUser::whereUserid($user->userid)->whereDialogId($dialogId)->first();
        if (!$dialogUser) {
            return Base::retError("会话不存在");
        }
        switch ($type) {
            case 'read':
                WebSocketDialogMsgRead::whereUserid($user->userid)
                    ->whereReadAt(null)
                    ->whereDialogId($dialogId)
                    ->chunkById(100, function ($list) {
                        WebSocketDialogMsgRead::onlyMarkRead($list);
                    });
                $dialogUser->mark_unread = 0;
                $dialogUser->save();
                break;

            case 'unread':
                $dialogUser->mark_unread = 1;
                $dialogUser->save();
                break;

            default:
                return Base::retError("参数错误");
        }
        return Base::retSuccess("success", [
            'id' => $dialogId,
            'mark_unread' => $dialogUser->mark_unread,
        ]);
    }

    /**
     * @api {get} api/dialog/group/add          14. 新增群组
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__add
     *
     * @apiParam {Array} userids                群成员，格式: [userid1, userid2, userid3]
     * @apiParam {String} chat_name             群名称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__add()
    {
        $user = User::auth();
        //
        $userids = Request::input('userids');
        $chatName = trim(Request::input('chat_name'));
        //
        if (!is_array($userids)) {
            return Base::retError('请选择群成员');
        }
        $userids = array_merge([$user->userid], $userids);
        $userids = array_values(array_filter(array_unique($userids)));
        if (count($userids) < 2) {
            return Base::retError('群成员至少2人');
        }
        //
        if (empty($chatName)) {
            $array = [];
            foreach ($userids as $userid) {
                $array[] = User::userid2nickname($userid);
                if (count($array) >= 8 || strlen(implode(", ", $array)) > 100) {
                    $array[] = "...";
                    break;
                }
            }
            $chatName = implode(", ", $array);
        }
        $dialog = WebSocketDialog::createGroup($chatName, $userids, 'user', $user->userid);
        if (empty($dialog)) {
            return Base::retError('创建群组失败');
        }
        $data = WebSocketDialog::find($dialog->id)?->formatData($user->userid);
        $dialog->pushMsg("groupAdd", $data, $userids);
        return Base::retSuccess('创建成功', $data);
    }

    /**
     * @api {get} api/dialog/group/edit          15. 修改群组
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__edit
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {String} chat_name             群名称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__edit()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $chatName = trim(Request::input('chat_name'));
        //
        if (mb_strlen($chatName) < 2) {
            return Base::retError('群名称至少2个字');
        }
        if (mb_strlen($chatName) > 100) {
            return Base::retError('群名称最长限制100个字');
        }
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, true);
        //
        $dialog->name = $chatName;
        $dialog->save();
        return Base::retSuccess('修改成功', [
            'id' => $dialog->id,
            'name' => $dialog->name,
        ]);
    }

    /**
     * @api {get} api/dialog/group/user          16. 获取群成员
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__user
     *
     * @apiParam {Number} dialog_id            会话ID
     * @apiParam {Number} [getuser]            获取会员详情（1: 返回会员昵称、邮箱等基本信息，0: 默认不返回）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__user()
    {
        User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $getuser = intval(Request::input('getuser', 0));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        $data = $dialog->dialogUser->toArray();
        if ($getuser === 1) {
            $array = [];
            foreach ($data as $item) {
                $res = User::userid2basic($item['userid']);
                if ($res) {
                    $array[] = array_merge($item, $res->toArray());
                }
            }
            $data = $array;
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/group/adduser          17. 添加群成员
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__adduser
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {Array} userids                新增的群成员，格式: [userid1, userid2, userid3]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__adduser()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $userids = Request::input('userids');
        //
        if (!is_array($userids)) {
            return Base::retError('请选择群成员');
        }
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, true);
        //
        $dialog->checkGroup();
        $dialog->joinGroup($userids);
        $dialog->pushMsg("groupJoin", $dialog->formatData($user->userid), $userids);
        return Base::retSuccess('添加成功');
    }

    /**
     * @api {get} api/dialog/group/deluser          18. 移出（退出）群成员
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__adduser
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {Array} userids                移出的群成员，格式: [userid1, userid2, userid3]
     * - 留空表示自己退出
     * - 有值表示移出，仅限群主操作
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__deluser()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $userids = Request::input('userids');
        //
        $type = 'remove';
        if (empty($userids)) {
            $type = 'exit';
            $userids = [$user->userid];
        }
        //
        if (!is_array($userids)) {
            return Base::retError('请选择群成员');
        }
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, $type === 'remove');
        //
        $dialog->checkGroup();
        $dialog->exitGroup($userids);
        $dialog->pushMsg("groupExit", null, $userids);
        return Base::retSuccess($type === 'remove' ? '移出成功' : '退出成功');
    }

    /**
     * @api {get} api/dialog/group/disband          19. 解散群组
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__disband
     *
     * @apiParam {Number} dialog_id             会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__disband()
    {
        User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, true);
        //
        $dialog->checkGroup();
        $dialog->deleteDialog();
        $dialog->pushMsg("groupDelete");
        return Base::retSuccess('解散成功');
    }
}
