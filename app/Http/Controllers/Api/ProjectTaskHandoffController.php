<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\ProjectTaskHandoff;
use App\Models\User;
use App\Module\Base;
use App\Module\ProjectTaskHandoffService;
use Request;

/**
 * @apiDefine projecttaskhandoff 任务流转
 */
class ProjectTaskHandoffController extends AbstractController
{
    /**
     * @api {get} api/projecttaskhandoff/lists 任务流转记录
     * @apiGroup projecttaskhandoff
     * @apiParam {Number} task_id 任务ID
     * @apiParam {Number} [before_id] 上一页最后一条记录ID
     * @apiParam {String} [department_owner_ids] 所选管理部门ID
     */
    public function lists()
    {
        User::auth();
        $task = ProjectTaskHandoffService::task((int)Request::input('task_id'));
        $query = ProjectTaskHandoff::where('task_id', $task->id);
        $beforeId = (int)Request::input('before_id');
        if ($beforeId > 0) {
            $query->where('id', '<', $beforeId);
        }
        $rows = $query->orderByDesc('id')->limit(21)->get();
        try {
            ProjectTaskHandoffService::policy($task);
            $canAssign = true;
        } catch (ApiException $e) {
            $canAssign = false;
        }
        return Base::retSuccess('success', [
            'lists' => $rows->take(20)->values(),
            'has_more' => $rows->count() > 20,
            'can_assign' => $canAssign,
        ]);
    }

    /**
     * @api {get} api/projecttaskhandoff/options 任务指派人员与权限
     * @apiGroup projecttaskhandoff
     * @apiParam {Number} task_id 任务ID
     * @apiParam {String} [department_owner_ids] 所选管理部门ID
     */
    public function options()
    {
        User::auth();
        $task = ProjectTaskHandoffService::task((int)Request::input('task_id'));
        return Base::retSuccess('success', ProjectTaskHandoffService::options($task));
    }

    /**
     * @api {post} api/projecttaskhandoff/assign 指派任务负责人并附带留言
     * @apiGroup projecttaskhandoff
     * @apiParam {Number} task_id 任务ID
     * @apiParam {Array} owners 完整的目标负责人ID列表，包含受保护的负责人
     * @apiParam {String} version options接口返回的并发校验值
     * @apiParam {String} [note] 指派留言，最多1000字
     * @apiParam {String} [department_owner_ids] 所选管理部门ID
     */
    public function assign()
    {
        User::auth();
        $owners = Request::input('owners', []);
        $note = Request::input('note') ?? '';
        $version = Request::input('version');
        if (!Request::isMethod('post')
            || !is_array($owners)
            || count($owners) > 10
            || !is_string($note)
            || mb_strlen($note) > 1000
            || !is_string($version)) {
            return Base::retError('指派参数无效');
        }
        foreach ($owners as $id) {
            if ((!is_int($id) && !is_string($id)) || !ctype_digit((string)$id) || (int)$id <= 0) {
                return Base::retError('指派参数无效');
            }
        }
        $task = ProjectTaskHandoffService::task((int)Request::input('task_id'));
        return Base::retSuccess('指派成功', ProjectTaskHandoffService::assign($task, $owners, $version, trim($note)));
    }
}
