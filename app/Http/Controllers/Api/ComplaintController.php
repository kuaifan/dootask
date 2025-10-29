<?php

namespace App\Http\Controllers\Api;

use Request;
use App\Models\User;
use App\Module\Base;
use App\Models\Complaint;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;

/**
 * @apiDefine complaint
 *
 * 投诉
 */
class ComplaintController extends AbstractController
{
    /**
     * @api {get} api/complaint/lists 获取举报投诉列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup complaint
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
     *
     * @apiSuccessExample {json} Success-Response-Data:
     *  {
     *      "current_page": 1,
     *      "data": [
     *          {
     *              "id": 1,
     *              "dialog_id": 100,
     *              "userid": 1,
     *              "type": 1,
     *              "reason": "举报原因",
     *              "imgs": [],
     *              "status": 0,
     *              "created_at": "2025-01-01 00:00:00",
     *              "updated_at": "2025-01-01 00:00:00"
     *          }
     *      ],
     *      "first_page_url": "http://example.com/api/complaint/lists?page=1",
     *      "from": 1,
     *      "last_page": 1,
     *      "last_page_url": "http://example.com/api/complaint/lists?page=1",
     *      "next_page_url": null,
     *      "path": "http://example.com/api/complaint/lists",
     *      "per_page": 50,
     *      "prev_page_url": null,
     *      "to": 1,
     *      "total": 1
     *  }
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
     * @api {post} api/complaint/submit 举报投诉
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup complaint
     * @apiName submit
     *
     * @apiBody {Number} dialog_id         对话ID
     * @apiBody {Number} type              类型
     * @apiBody {String} reason            原因
     * @apiBody {Object[]} [imgs]          图片数组（可选）
     * @apiBody {String} imgs.path         图片路径
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     *
     * @apiSuccessExample {json} Success-Response-Data:
     *  []
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
                    WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                        'type' => 'content',
                        'title' => '收到新的举报信息',
                        'content' => "收到新的举报信息：{$reason} (请前往应用查看详情)"
                    ], $botUser->userid);
                }
            });
        //
        return Base::retSuccess('success');
    }

    /**
     * @api {post} api/complaint/action 举报投诉 - 操作
     *
     * @apiDescription 需要token身份（管理员权限）
     * @apiVersion 1.0.0
     * @apiGroup complaint
     * @apiName action
     *
     * @apiBody {Number} id                投诉ID
     * @apiBody {String} type              操作类型：handle=已处理，delete=删除
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     *
     * @apiSuccessExample {json} Success-Response-Data:
     *  []
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
