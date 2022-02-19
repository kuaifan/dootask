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
        $list = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('u.userid', $user->userid)
            ->orderByDesc('u.top_at')
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
        Base::checkClientVersion('0.8.1');
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
        if (mb_strlen($text) < 1) {
            return Base::retError('消息内容不能为空');
        } elseif (mb_strlen($text) > 20000) {
            return Base::retError('消息内容最大不能超过20000字');
        }
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
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
            $codeExt = ['txt'];
            $officeExt = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
            $localExt = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw', 'tif', 'tiff', 'mp3', 'wav', 'mp4', 'flv', 'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm'];
            $msg = Base::json2array($dialogMsg->getRawOriginal('msg'));
            $filePath = public_path($msg['path']);
            if (in_array($msg['ext'], $codeExt) && $msg['size'] < 2 * 1024 * 1024) {
                // 文本预览，限制2M内的文件
                $data['content'] = file_get_contents($filePath);
                $data['file_mode'] = 1;
            } elseif (in_array($msg['ext'], $officeExt)) {
                // office预览
                $data['file_mode'] = 2;
            } else {
                // 其他预览
                if (in_array($msg['ext'], $localExt)) {
                    $url = Base::fillUrl($msg['path']);
                } else {
                    $url = 'http://' . env('APP_IPPR') . '.3/' . $msg['path'];
                }
                $data['url'] = base64_encode($url);
                $data['file_mode'] = 3;
            }
        }
        //
        return Base::retSuccess("success", $data);
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
        return Base::retSuccess("success", $dialogId);
    }
}
