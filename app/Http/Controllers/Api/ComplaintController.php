<?php

namespace App\Http\Controllers\Api;

use Request;
use App\Models\User;
use App\Module\Base;
use App\Models\Complaint;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;

/**
 * @apiDefine dialog
 *
 * 投诉
 */
class ComplaintController extends AbstractController
{
    /**
     * @api {get} api/complaint/lists          01. 获取举报投诉列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName lists
     *
     * @apiParam {Number} [type]              类型
     * @apiParam {Number} [status]            状态
     *
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:50，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function lists()
    {
        $user = User::auth();
        $user->identity('admin');
        //
        $type = intval(Request::input('type'));
        $status = Request::input('status');
        //
        $complaints = Complaint::query()
            ->when($type, function($q) use($type) {
                $q->where('type', $type);
            })
            ->when($status != "", function($q) use($status) {
                $q->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(Base::getPaginate(100, 50));
        //
        return Base::retSuccess('success', $complaints);
    }

    /**
     * @api {get} api/complaint/submit          02. 举报投诉
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName submit
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {Number} type              类型
     * @apiParam {String} reason            原因
     * @apiParam {String} imgs              图片
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function submit()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $type = intval(Request::input('type'));
        $reason = trim(Request::input('reason'));
        $imgs = Request::input('imgs');
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        if (!$type) {
            return Base::retError('请选择举报类型');
        }
        if (!$reason) {
            return Base::retError('请填写举报原因');
        }
        //
        $report_imgs = [];
        if (!empty($imgs) && is_array($imgs)) {
            foreach ($imgs as $img) {
                $report_imgs[] = Base::unFillUrl($img['path']);
            }
        }
        //
        Complaint::createInstance([
            'dialog_id' => $dialog_id,
            'userid' => $user->userid,
            'type' => $type,
            'reason' => $reason,
            'imgs' => $report_imgs,
        ])->save();
        // 通知管理员
        $botUser = User::botGetOrCreate('system-msg');
        User::where("identity", "like", "%,admin,%")
            ->orderByDesc('line_at')
            ->take(10)
            ->get()
            ->each(function ($adminUser) use ($reason, $botUser) {
                $dialog = WebSocketDialog::checkUserDialog($botUser, $adminUser->userid);
                if ($dialog) {
                    $text = "<p>收到新的举报信息：{$reason} (请前往应用查看详情)</p>";
                    WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid);   // todo 未能在任务end事件来发送任务
                }
            });
        //
        return Base::retSuccess('success');
    }

    /**
     * @api {get} api/complaint/action          03. 举报投诉 - 操作
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName action
     *
     * @apiParam {Number} id                ID
     * @apiParam {Number} type              类型
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function action()
    {
        $user = User::auth();
        $user->identity('admin');
        //
        $id = intval(Request::input('id'));
        $type = trim(Request::input('type'));
        //
        if ($type == 'handle') {
            Complaint::whereId($id)->update([
                "status" => 1
            ]);
        }
        if ($type == 'delete') {
            Complaint::whereId($id)->delete();
        }
        //
        return Base::retSuccess('success');
    }
}
