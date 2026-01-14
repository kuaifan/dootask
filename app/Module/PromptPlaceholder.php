<?php

namespace App\Module;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\UserTag;
use App\Models\WebSocketDialog;
use Cache;
use Carbon\Carbon;
use DB;

/**
 * AI 提示词模块
 *
 * 提供用户上下文和条件性提示块的构建能力
 */
class PromptPlaceholder
{
    /**
     * 构建条件性提示块（用户上下文 + 格式指南）
     *
     * @param int|null $userid
     * @param WebSocketDialog|null $dialog
     * @return string
     */
    public static function buildOptionalPrompts($userid, ?WebSocketDialog $dialog = null): string
    {
        $blocks = [];

        // 用户上下文块
        if ($userid && $userid > 0) {
            $userContext = self::buildUserContext($userid, $dialog);
            if ($userContext) {
                $blocks[] = <<<EOF
                    <optional-user-context>
                    以下是当前对话用户的背景信息，当需要了解用户身份、工作职责或任务情况时可参考：

                    {$userContext}

                    注意：此上下文仅供参考，用于理解用户背景和提供个性化帮助。如果与当前对话无关，请忽略。
                    </optional-user-context>
                    EOF;
            }
        }

        // 格式指南块
        $blocks[] = <<<'EOF'
            <optional-format-guide>
            当你的回答中包含 DooTask 系统资源（任务、项目、文件等）时，建议使用以下链接格式使其可点击：
            - 任务: [任务名称](dootask://task/{task_id}/{parent_id})，其中 parent_id 为主任务ID，主任务时为 0
            - 项目: [项目名称](dootask://project/{project_id})
            - 文件: [文件名称](dootask://file/{file_id})
            - 联系人: [用户名](dootask://contact/{userid})
            - 消息: [消息预览](dootask://message/{dialog_id}/{msg_id})

            注意：此格式指南不影响正常对话，仅在涉及上述资源时参考。如果与当前对话无关，请忽略。
            </optional-format-guide>
            EOF;

        return implode("\n\n", $blocks);
    }

    /**
     * 构建完整用户上下文
     */
    private static function buildUserContext(int $userid, ?WebSocketDialog $dialog = null): string
    {
        $lines = [];

        // 基础信息
        $basicInfo = self::getUserBasicInfo($userid);
        $nickname = $basicInfo['nickname'] ?? '';
        if ($nickname) {
            $basicLine = "与您对话的用户：{$nickname}";
            if ($basicInfo['profession'] ?? '') {
                $basicLine .= "（{$basicInfo['profession']}）";
            }
            $lines[] = "{$basicLine}（user_id: {$userid}）";
        }

        if ($basicInfo['department'] ?? '') {
            $lines[] = "所属部门：{$basicInfo['department']}";
        }

        if ($basicInfo['introduction'] ?? '') {
            $lines[] = "个人简介：{$basicInfo['introduction']}";
        }

        // 同事印象
        $tags = self::getUserTags($userid);
        if ($tags) {
            $lines[] = "同事印象：{$tags}";
        }

        // 场景角色
        if ($dialog) {
            $role = self::getUserRole($userid, $dialog);
            if ($role) {
                $lines[] = $role;
            }
        }

        // 进行中任务
        $inProgressTasks = self::getInProgressTasks($userid);
        if ($inProgressTasks) {
            $lines[] = "\n进行中的任务：\n{$inProgressTasks}";
        }

        // 最近完成
        $completedTasks = self::getCompletedTasks($userid);
        if ($completedTasks) {
            $lines[] = "\n最近完成：\n{$completedTasks}";
        }

        return implode("\n", $lines);
    }

    /**
     * 获取用户基础信息
     */
    private static function getUserBasicInfo(int $userid): array
    {
        $user = User::find($userid);
        if (!$user) {
            return [];
        }

        return [
            'nickname' => $user->nickname ?: '',
            'profession' => $user->profession ?: '',
            'introduction' => $user->introduction ? mb_substr($user->introduction, 0, 100) : '',
            'department' => $user->getDepartmentName() ?: '',
        ];
    }

    /**
     * 获取用户标签 Top 5
     */
    private static function getUserTags(int $userid): string
    {
        $tags = UserTag::where('user_id', $userid)
            ->withCount(['recognitions as recognition_total'])
            ->orderByDesc('recognition_total')
            ->orderBy('id')
            ->take(5)
            ->pluck('name')
            ->toArray();

        return implode('、', $tags);
    }

    /**
     * 获取用户在场景中的角色
     */
    private static function getUserRole(int $userid, WebSocketDialog $dialog): string
    {
        if ($dialog->type !== 'group') {
            return '';
        }

        switch ($dialog->group_type) {
            case 'project':
                $project = Project::whereDialogId($dialog->id)->first();
                if ($project) {
                    $projectUser = ProjectUser::whereProjectId($project->id)->whereUserid($userid)->first();
                    if ($projectUser?->owner) {
                        return '该用户是此项目的负责人';
                    }
                }
                break;

            case 'task':
                $task = ProjectTask::whereDialogId($dialog->id)->first();
                if ($task) {
                    $taskUser = ProjectTaskUser::whereTaskId($task->id)->whereUserid($userid)->first();
                    if ($taskUser) {
                        return $taskUser->owner ? '该用户是此任务的负责人' : '该用户是此任务的协助人';
                    }
                }
                break;

            case 'department':
                $department = UserDepartment::whereDialogId($dialog->id)->first();
                if ($department?->owner_userid === $userid) {
                    return '该用户是此部门的负责人';
                }
                break;
        }

        return '';
    }

    /**
     * 获取进行中的任务（缓存 3 分钟）
     *
     * 排序策略：逾期优先 → 最近活跃优先 → 负责人优先 → 高优先级优先 → 截止时间近优先
     */
    private static function getInProgressTasks(int $userid): string
    {
        $cacheKey = "prompt:tasks:in_progress:{$userid}";

        return Cache::remember($cacheKey, 180, function () use ($userid) {
            $now = Carbon::now();
            $threeDaysAgo = $now->copy()->subDays(3);

            // orderByRaw 中的表名需要带前缀
            $prefix = DB::getTablePrefix();
            $t = $prefix . 'project_tasks';
            $du = $prefix . 'web_socket_dialog_users';

            $tasks = ProjectTask::query()
                ->select([
                    'project_tasks.id', 
                    'project_tasks.name', 
                    'project_tasks.p_name', 
                    'project_tasks.end_at', 
                    'project_task_users.owner'
                ])
                ->join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
                ->leftJoin('web_socket_dialog_users', function ($join) use ($userid) {
                    $join->on('project_tasks.dialog_id', '=', 'web_socket_dialog_users.dialog_id')
                        ->where('web_socket_dialog_users.userid', '=', $userid);
                })
                ->where('project_task_users.userid', $userid)
                ->where('project_tasks.visibility', 1)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->whereNull('project_tasks.deleted_at')
                ->orderByRaw("CASE WHEN {$t}.end_at IS NOT NULL AND {$t}.end_at < ? THEN 0 ELSE 1 END", [$now])
                ->orderByRaw("CASE WHEN {$du}.last_at >= ? THEN 0 ELSE 1 END", [$threeDaysAgo])
                ->orderByDesc('web_socket_dialog_users.last_at')
                ->orderByDesc('project_task_users.owner')
                ->orderByDesc('project_tasks.p_level')
                ->orderByRaw("CASE WHEN {$t}.end_at IS NULL THEN 1 ELSE 0 END")
                ->orderBy('project_tasks.end_at')
                ->take(20)
                ->get();

            return self::formatTaskList($tasks, $now);
        });
    }

    /**
     * 获取最近完成的任务（缓存 3 分钟）
     */
    private static function getCompletedTasks(int $userid): string
    {
        $cacheKey = "prompt:tasks:completed:{$userid}";

        return Cache::remember($cacheKey, 180, function () use ($userid) {
            $tasks = ProjectTask::query()
                ->select([
                    'project_tasks.id', 
                    'project_tasks.name'
                ])
                ->join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
                ->where('project_task_users.userid', $userid)
                ->where('project_tasks.visibility', 1)
                ->whereNotNull('project_tasks.complete_at')
                ->where('project_tasks.complete_at', '>=', Carbon::now()->subDays(7))
                ->whereNull('project_tasks.deleted_at')
                ->orderByDesc('project_tasks.complete_at')
                ->take(5)
                ->get();

            if ($tasks->isEmpty()) {
                return '';
            }

            return $tasks->map(fn($task) => "- {$task->name} (task:{$task->id})")->implode("\n");
        });
    }

    /**
     * 格式化任务列表
     */
    private static function formatTaskList($tasks, Carbon $now): string
    {
        if ($tasks->isEmpty()) {
            return '';
        }

        return $tasks->map(function ($task) use ($now) {
            $line = '- ';
            if ($task->p_name) {
                $line .= "[{$task->p_name}] ";
            }
            $line .= "{$task->name} (task_id:{$task->id})";
            if ($task->end_at && Carbon::parse($task->end_at)->lt($now)) {
                $line .= ' ⚠️逾期';
            }
            return $line;
        })->implode("\n");
    }
}
