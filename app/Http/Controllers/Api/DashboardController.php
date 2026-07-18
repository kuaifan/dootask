<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Base;
use App\Module\DashboardTeam;
use Request;

/**
 * @apiDefine dashboard
 *
 * 仪表盘
 */
class DashboardController extends AbstractController
{
    /**
     * @api {get} api/dashboard/team/stats 负责人视角统计
     *
     * @apiDescription 需要token身份。返回所选管理部门（含下级部门）的团队任务统计。
     * @apiVersion 1.0.0
     * @apiGroup dashboard
     * @apiName team__stats
     *
     * @apiParam {String} [department_owner_ids] 所选管理部门ID，逗号分隔；不传表示全部
     * @apiParam {Number} [refresh] 传 1 时主动刷新当前统计缓存
     *
     * @apiSuccess {Number} ret  返回状态码
     * @apiSuccess {String} msg  返回信息
     * @apiSuccess {Object} data 团队统计数据
     */
    public function team__stats()
    {
        $user = User::auth();
        $context = DashboardTeam::context($user, Request::input('department_owner_ids'));
        return Base::retSuccess('success', DashboardTeam::stats($context, intval(Request::input('refresh')) === 1));
    }

    /**
     * @api {get} api/dashboard/team/tasks 负责人视角任务列表
     *
     * @apiDescription 需要token身份。按关注类型、成员或优先级分页返回团队任务。
     * @apiVersion 1.0.0
     * @apiGroup dashboard
     * @apiName team__tasks
     *
     * @apiParam {String} [department_owner_ids] 所选管理部门ID，逗号分隔；不传表示全部
     * @apiParam {String} [type] 任务类型：uncompleted/overdue/soon/hi/noowner
     * @apiParam {Number} [member_id] 成员ID；传入后优先于 type
     * @apiParam {Number} [level] 优先级；-1 表示未设置，传入后优先于 type
     * @apiParam {Number} [page] 当前页
     * @apiParam {Number} [pagesize] 每页数量，默认20，最大50
     *
     * @apiSuccess {Number} ret  返回状态码
     * @apiSuccess {String} msg  返回信息
     * @apiSuccess {Object} data 分页任务数据
     */
    public function team__tasks()
    {
        $user = User::auth();
        $context = DashboardTeam::context($user, Request::input('department_owner_ids'));

        $memberId = intval(Request::input('member_id'));
        $levelValue = Request::input('level');
        $level = $levelValue !== null && $levelValue !== '' ? intval($levelValue) : null;
        $type = trim((string)Request::input('type'));

        if ($memberId > 0) {
            if (!in_array($memberId, $context['member_userids'], true)) {
                return Base::retError('参数错误');
            }
        } elseif ($level !== null) {
            if ($level !== -1 && !in_array($level, DashboardTeam::priorityLevels(), true)) {
                return Base::retError('参数错误');
            }
        } elseif (!in_array($type, ['uncompleted', 'overdue', 'soon', 'hi', 'noowner'], true)) {
            return Base::retError('参数错误');
        }

        return Base::retSuccess('success', DashboardTeam::tasks($context, [
            'type' => $type,
            'member_id' => $memberId,
            'level' => $level,
        ]));
    }
}
