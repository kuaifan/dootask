<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
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
        //
        return Base::retSuccess('success', $list);
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
        if ($dialog->type == 'group') {
            return WebSocketDialogMsg::addGroupMsg($dialog_id, 'text', $msg, $user->userid, $extra_int, $extra_str);
        } else {
            return WebSocketDialogMsg::addUserMsg($dialog_id, 'text', $msg, $user->userid, $extra_int, $extra_str);
        }
    }
}
