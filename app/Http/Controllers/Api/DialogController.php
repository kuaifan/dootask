<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Models\WebSocketDialogUser;
use App\Module\Base;
use Request;

/**
 * @apiDefine dialog
 *
 * 对话
 */
class DialogController extends AbstractController
{
    /**
     * 对话列表
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:100，最大:200
     */
    public function lists()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
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
     * 单个对话信息
     *
     * @apiParam {Number} dialog_id         对话ID
     */
    public function one()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
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
     * 打开会话
     *
     * @apiParam {Number} userid         对话会员ID
     */
    public function open__user()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $userid = intval(Request::input('userid'));
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
     * 消息列表
     *
     * @apiParam {Number} dialog_id         对话ID
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:50，最大:100
     */
    public function msg__lists()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        if (!WebSocketDialogUser::whereDialogId($dialog_id)->whereUserid($user->userid)->exists()) {
            return Base::retError('不在成员列表内');
        }
        $dialog = WebSocketDialog::whereId($dialog_id)->first();
        if (empty($dialog)) {
            return Base::retError('对话不存在或已被删除');
        }
        //
        $list = WebSocketDialogMsg::whereDialogId($dialog_id)->orderByDesc('id')->paginate(Base::getPaginate(100, 50));
        $list->transform(function (WebSocketDialogMsg $item) use ($user) {
            $item->r = $item->userid === $user->userid ? null : WebSocketDialogMsgRead::whereMsgId($item->id)->whereUserid($user->userid)->first();
            return $item;
        });
        //
        $data = $list->toArray();
        $data['dialog'] = WebSocketDialog::formatData($dialog, $user->userid);
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * 未读消息
     */
    public function msg__unread()
    {
        $unread = WebSocketDialogMsgRead::whereUserid(User::token2userid())->whereReadAt(null)->count();
        return Base::retSuccess('success', [
            'unread' => $unread,
        ]);
    }

    /**
     * 发送消息
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {Number} [extra_int]       额外参数（数字）
     * @apiParam {String} [extra_str]       额外参数（字符）
     * @apiParam {String} text              消息内容
     */
    public function msg__sendtext()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $extra_int = intval(Request::input('extra_int'));
        $extra_str = trim(Request::input('extra_str'));
        $text = trim(Request::input('text'));
        //
        if (mb_strlen($text) < 1) {
            return Base::retError('消息内容不能为空');
        } elseif (mb_strlen($text) > 20000) {
            return Base::retError('消息内容最大不能超过20000字');
        }
        //
        if (!WebSocketDialogUser::whereDialogId($dialog_id)->whereUserid($user->userid)->exists()) {
            return Base::retError('不在成员列表内');
        }
        $dialog = WebSocketDialog::whereId($dialog_id)->first();
        if (empty($dialog)) {
            return Base::retError('对话不存在或已被删除');
        }
        //
        $msg = [
            'text' => $text
        ];
        //
        return WebSocketDialogMsg::sendMsg($dialog_id, 'text', $msg, $user->userid, $extra_int, $extra_str);
    }

    /**
     * {post}文件上传
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {Number} [extra_int]       额外参数（数字）
     * @apiParam {String} [extra_str]       额外参数（字符）
     * @apiParam {String} [filename]        post-文件名称
     * @apiParam {String} [image64]         post-base64图片（二选一）
     * @apiParam {File} [files]             post-文件对象（二选一）
     */
    public function msg__sendfile()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $dialog_id = Base::getPostInt('dialog_id');
        $extra_int = Base::getPostInt('extra_int');
        $extra_str = Base::getPostValue('extra_str');
        //
        if (!WebSocketDialogUser::whereDialogId($dialog_id)->whereUserid($user->userid)->exists()) {
            return Base::retError('不在成员列表内');
        }
        $dialog = WebSocketDialog::whereId($dialog_id)->first();
        if (empty($dialog)) {
            return Base::retError('对话不存在或已被删除');
        }
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
            $fileData['thumb'] = $fileData['thumb'] ?: 'images/ext/file.png';
            switch ($fileData['ext']) {
                case "docx":
                    $fileData['thumb'] = 'images/ext/doc.png';
                    break;
                case "xlsx":
                    $fileData['thumb'] = 'images/ext/xls.png';
                    break;
                case "pptx":
                    $fileData['thumb'] = 'images/ext/ppt.png';
                    break;
                case "ai":
                case "avi":
                case "bmp":
                case "cdr":
                case "doc":
                case "eps":
                case "gif":
                case "mov":
                case "mp3":
                case "mp4":
                case "pdf":
                case "ppt":
                case "pr":
                case "psd":
                case "rar":
                case "svg":
                case "tif":
                case "txt":
                case "xls":
                case "zip":
                    $fileData['thumb'] = 'images/ext/' . $fileData['ext'] . '.png';
                    break;
            }
            //
            $msg = $fileData;
            $msg['size'] *= 1024;
            //
            return WebSocketDialogMsg::sendMsg($dialog_id, 'file', $msg, $user->userid, $extra_int, $extra_str);
        }
    }

    /**
     * 获取消息阅读情况
     *
     * @apiParam {Number} msg_id            消息ID（需要是消息的发送人）
     */
    public function msg__readlist()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
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
}
