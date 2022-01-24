<?php

namespace App\Http\Controllers\Api;

use App\Models\ProjectTask;
use App\Models\ProjectTaskFile;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Models\WebSocketDialogUser;
use App\Module\Base;
use Carbon\Carbon;
use Request;

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
        $list = WebSocketDialog::select(['web_socket_dialogs.*'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('u.userid', $user->userid)
            ->orderByDesc('web_socket_dialogs.last_at')
            ->paginate(Base::getPaginate(200, 100));
        $list->transform(function (WebSocketDialog $item) use ($user) {
            return WebSocketDialog::formatData($item, $user->userid);
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
        $item = WebSocketDialog::select(['web_socket_dialogs.*'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('web_socket_dialogs.id', $dialog_id)
            ->where('u.userid', $user->userid)
            ->first();
        if ($item) {
            $item = WebSocketDialog::formatData($item, $user->userid);
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
        if ($userid == $user->userid) {
            return Base::retError('不能对话自己');
        }
        //
        $dialog = WebSocketDialog::checkUserDialog($user->userid, $userid);
        if (empty($dialog)) {
            return Base::retError('打开会话失败');
        }
        $data = WebSocketDialog::formatData(WebSocketDialog::find($dialog->id), $user->userid);
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
        $list = WebSocketDialogMsg::whereDialogId($dialog_id)->orderByDesc('id')->paginate(Base::getPaginate(100, 50));
        $list->transform(function (WebSocketDialogMsg $item) use ($user) {
            $item->is_read = $item->userid === $user->userid || WebSocketDialogMsgRead::whereMsgId($item->id)->whereUserid($user->userid)->value('read_at');
            return $item;
        });
        //
        if ($dialog->type == 'group' && $dialog->group_type == 'task') {
            $user->task_dialog_id = $dialog->id;
            $user->save();
        }
        //
        $data = $list->toArray();
        if ($list->currentPage() === 1) {
            $data['dialog'] = WebSocketDialog::formatData($dialog, $user->userid);
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/sendtext          05. 未读消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendtext
     */
    public function msg__unread()
    {
        $unread = WebSocketDialogMsgRead::whereUserid(User::userid())->whereReadAt(null)->count();
        return Base::retSuccess('success', [
            'unread' => $unread,
        ]);
    }

    /**
     * @api {get} api/dialog/msg/sendtext          06. 发送消息
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
        $dialog_id = intval(Request::input('dialog_id'));
        $text = trim(Request::input('text'));
        //
        if (mb_strlen($text) < 1) {
            return Base::retError('消息内容不能为空');
        } elseif (mb_strlen($text) > 20000) {
            return Base::retError('消息内容最大不能超过20000字');
        }
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        $msg = [
            'text' => $text
        ];
        //
        return WebSocketDialogMsg::sendMsg($dialog_id, 'text', $msg, $user->userid);
    }

    /**
     * @api {post} api/dialog/msg/sendfile          07. 文件上传
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendfile
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {String} [filename]        post-文件名称
     * @apiParam {String} [image64]         post-base64图片（二选一）
     * @apiParam {File} [files]             post-文件对象（二选一）
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
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        $path = "uploads/chat/" . $user->userid . "/";
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
                "type" => 'file',
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
            if ($dialog->type === 'group') {
                if ($dialog->group_type === 'task') {
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
     * 聊天消息撤回
     * @return array
     */
    public function msg__withdraw()
    {
        $user = User::auth();
        $msg_id = intval(Request::input("msg_id"));
        $msg = WebSocketDialogMsg::whereId($msg_id)->whereUserid($user->userid)->first();
        if (empty($msg)) {
            return Base::retError("此消息不可撤回");
        }
        $send_dt = Carbon::parse($msg->created_at)->addMinutes(5);
        if ( $send_dt->lt( Carbon::now() ) )
            return Base::retError("已超过5分钟，此消息不能撤回");


        // 删除文件、图片
        if ( $msg->type == WebSocketDialogMsg::MSG_TYPE_FILE) {
            if (is_array($msg->msg)) {
                // 删除本体
                if ( !empty( $msg->msg["file"] ) )
                    @unlink( $msg->msg["file"] );
                // 删除缩略图
                if ( !empty( $msg->msg["thumb"] ) )
                    @unlink( $msg->msg["thumb"] );
            }
        }

        // 直接删除消息
        $msg->delete();

        /* 原始需求：消息直接删除，无需提示 */
        // 发送撤回指令
//        WebSocketDialogMsg::sendMsg($msg->dialog_id, 'withdraw', [
//            "msg_id" => $msg->id, // 被撤回的消息Id
//        ], $user->userid);
        return Base::retSuccess("success");
    }
}
