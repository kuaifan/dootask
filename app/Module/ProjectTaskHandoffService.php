<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\Project;
use App\Models\ProjectPermission;
use App\Models\ProjectTask;
use App\Models\ProjectTaskHandoff;
use App\Models\ProjectTaskUser;
use App\Models\ProjectTaskVisibilityUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\UserDepartment;

class ProjectTaskHandoffService
{
    /**
     * 按任务详情的可见范围获取任务。
     */
    public static function task(int $taskId): ProjectTask
    {
        if (ProjectTaskHandoffSettings::get()['project_task_handoff'] !== 'open') {
            throw new ApiException('任务流转未开启');
        }
        $task = ProjectTask::findForDepartmentView($taskId, null);
        $userid = Doo::userId();
        // 私密任务还需校验任务身份，不能仅凭项目成员身份访问。
        if ((int)$task->visibility !== 1) {
            $projectOwner = ProjectUser::whereProjectId($task->project_id)->whereUserid($userid)
                ->whereIn('owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY])->exists();
            $participant = ProjectTaskUser::whereUserid($userid)->where(function ($query) use ($taskId) {
                $query->where('task_id', $taskId)->orWhere('task_pid', $taskId);
            })->exists();
            if (!$projectOwner && !$participant && !ProjectTaskVisibilityUser::whereTaskId($taskId)->whereUserid($userid)->exists()) {
                throw new ApiException('无任务权限');
            }
        }
        return $task;
    }

    /**
     * 校验指派权限，计算候选人员和不可移除的负责人。
     */
    public static function policy(ProjectTask $task): array
    {
        $settings = ProjectTaskHandoffSettings::get();
        $project = Project::find($task->project_id);
        if ($settings['project_task_handoff'] !== 'open' || !$project || $project->archived_at || $task->archived_at) {
            throw new ApiException('当前任务不可指派');
        }
        $userid = Doo::userId();
        $owners = ProjectTaskHandoffRecord::owners($task->id);
        $members = ProjectUser::whereProjectId($task->project_id)
            ->pluck('userid')
            ->map(fn ($id) => (int)$id)
            ->all();
        $ordinary = false;
        if (in_array($userid, $members, true)) {
            $authProject = Project::userProject($project->id);
            if ($owners) {
                ProjectPermission::userTaskPermission($authProject, ProjectPermission::TASK_UPDATE, $task);
            }
            $ordinary = true;
        }
        $protected = [];
        $candidates = $members;
        if (!$ordinary) {
            $context = UserDepartment::ownerViewContext(User::auth(), true);
            if ($settings['project_task_handoff_role'] === 'close'
                || !UserDepartment::isDepartmentReadonlyProject($context, $project->id)
                || (int)$task->visibility !== 1) {
                throw new ApiException('无指派权限');
            }
            $departments = UserDepartment::getManagedDepartments($userid);
            if ($settings['project_task_handoff_role'] === 'owner') {
                $departments = $departments->where('owner_userid', $userid);
            }
            $selectedScope = UserDepartment::getManagedDepartmentScopeIds(
                $userid,
                request()->input('department_owner_ids', request()->input('department_ids'))
            );
            $roots = $departments->pluck('id')->map(fn ($id) => (int)$id)->all();
            $scope = $roots ? array_values(array_intersect(
                UserDepartment::getManagedDepartmentScopeIds($userid, $roots),
                $selectedScope
            )) : [];
            if (!$scope) {
                throw new ApiException('无指派权限');
            }
            $managed = User::where(function ($query) use ($scope) {
                foreach ($scope as $id) {
                    $query->orWhere('department', 'like', "%,{$id},%");
                }
            })->pluck('userid')->map(fn ($id) => (int)$id)->all();
            // 项目访问权限必须来自符合指派设置的部门，不能借用其他部门的管理员身份。
            if (!array_intersect($managed, $members)) {
                throw new ApiException('无指派权限');
            }
            if ($settings['project_task_handoff_candidates'] === 'department') {
                $candidates = array_values(array_intersect($members, $managed));
            }
            if ($settings['project_task_handoff_adjust'] === 'department') {
                $protected = array_values(array_diff($owners, $managed));
            }
        }
        $candidates = User::whereIn('userid', $candidates)
            ->whereNull('disable_at')
            ->where('bot', 0)
            ->pluck('userid')
            ->map(fn ($id) => (int)$id)
            ->all();
        return [
            'owners' => $owners,
            'protected' => $protected,
            'candidates' => $candidates,
            'note_required' => $settings['project_task_handoff_note'] === 'required',
            'version' => self::version($task),
        ];
    }

    public static function version(ProjectTask $task): string
    {
        return hash('sha256', json_encode([
            $task->project_id,
            ProjectTaskHandoffRecord::owners($task->id),
            ProjectTaskHandoff::where('task_id', $task->id)->max('id'),
        ]));
    }

    public static function options(ProjectTask $task): array
    {
        $policy = self::policy($task);
        $policy['users'] = User::select(['userid', 'email', 'nickname', 'userimg'])
            ->whereIn('userid', array_unique(array_merge($policy['candidates'], $policy['owners'])))
            ->orderBy('nickname')
            ->get()
            ->makeHidden(['email'])
            ->toArray();
        return $policy;
    }

    public static function validateOwners(array $owners, array $policy, string $version, string $note): array
    {
        $owners = array_values(array_unique(array_map('intval', $owners)));
        sort($owners);
        if (!hash_equals($policy['version'], $version)) {
            throw new ApiException('负责人已发生变化，请刷新后重试');
        }
        if (count($owners) > 10) {
            throw new ApiException('任务负责人最多不能超过10个');
        }
        if (array_diff($policy['protected'], $owners)) {
            throw new ApiException('不能移除管理范围外的负责人');
        }
        $added = array_diff($owners, $policy['owners']);
        if (array_diff($added, $policy['candidates'])) {
            throw new ApiException('所选负责人不在可指派范围内');
        }
        if ($owners === $policy['owners']) {
            throw new ApiException('负责人未发生变化');
        }
        if ($policy['note_required'] && $note === '') {
            throw new ApiException('请填写指派留言');
        }
        return $owners;
    }

    /**
     * 在同一事务中更新负责人、记录留言，并复用任务通知。
     */
    public static function assign(ProjectTask $task, array $owners, string $version, string $note): array
    {
        return ProjectTaskHandoffRecord::track($task, 'assign', function () use ($task, $owners, $version, $note) {
            $authorized = self::task($task->id);
            $policy = self::policy($authorized);
            $owners = self::validateOwners($owners, $policy, $version, $note);
            $projectMembers = ProjectUser::whereProjectId($task->project_id)->whereIn('userid', $owners)->pluck('userid')->all();
            if (array_diff($owners, $projectMembers)) {
                throw new ApiException('所选负责人不在可指派范围内');
            }
            $marking = [];
            $task->updateTask(['owner' => $owners], $marking);
            $data = ProjectTask::oneTask($task->id)->toArray();
            $data['update_marking'] = $marking;
            $data['visibility_appointor'] = ProjectTaskVisibilityUser::whereTaskId($task->id)->pluck('userid')->all();
            $task->pushMsg('update', $data);
            $removed = array_values(array_diff($policy['owners'], $owners));
            $visibilityTasks = [$task];
            if ($task->parent_id && ($parent = ProjectTask::find($task->parent_id))) {
                $visibilityTasks[] = $parent;
            }
            foreach ($visibilityTasks as $visibleTask) {
                if (!$removed || (int)$visibleTask->visibility === 1) {
                    continue;
                }
                $remaining = ProjectTaskUser::where(function ($query) use ($visibleTask) {
                    $query->where('task_id', $visibleTask->id)->orWhere('task_pid', $visibleTask->id);
                })->pluck('userid')->all();
                $appointed = ProjectTaskVisibilityUser::whereTaskId($visibleTask->id)->pluck('userid')->all();
                $lostAccess = array_values(array_diff($removed, $remaining, $appointed));
                if ($lostAccess) {
                    $visibleTask->pushMsgVisibleRemove($lostAccess);
                }
            }
            return $data;
        }, $note);
    }
}
