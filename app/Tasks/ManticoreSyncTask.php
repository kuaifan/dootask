<?php

namespace App\Tasks;

use App\Models\File;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Manticore\ManticoreBase;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreUser;
use App\Module\Manticore\ManticoreProject;
use App\Module\Manticore\ManticoreTask;
use App\Module\Manticore\ManticoreMsg;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 通用 Manticore Search 同步任务（MVA 权限方案）
 *
 * 支持文件、用户、项目、任务的同步操作
 * 使用 MVA (Multi-Value Attribute) 内联权限过滤
 */
class ManticoreSyncTask extends AbstractTask
{
    private $action;

    private $data;

    public function __construct($action = null, $data = null)
    {
        parent::__construct(...func_get_args());
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * 获取任务动作类型（用于去重）
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * 获取数据ID（用于去重）
     *
     * @return int|null
     */
    public function getDataId(): ?int
    {
        if (!is_array($this->data)) {
            return null;
        }

        // 根据不同的 action 类型提取对应的 ID
        return $this->data['id']
            ?? $this->data['userid']
            ?? $this->data['file_id']
            ?? $this->data['project_id']
            ?? $this->data['task_id']
            ?? $this->data['msg_id']
            ?? $this->data['dialog_id']
            ?? null;
    }

    public function start()
    {
        if (!Apps::isInstalled("search")) {
            return;
        }

        switch ($this->action) {
            // ==============================
            // 文件同步动作
            // ==============================
            case 'file_sync':
                $file = File::find($this->data['id'] ?? 0);
                if ($file) {
                    ManticoreFile::sync($file);
                }
                break;

            case 'file_delete':
                $fileId = $this->data['id'] ?? 0;
                if ($fileId > 0) {
                    ManticoreFile::delete($fileId);
                }
                break;

            case 'file_pshare_update':
                $fileIds = $this->data['file_ids'] ?? [];
                $pshare = $this->data['pshare'] ?? 0;
                if (!empty($fileIds)) {
                    ManticoreBase::batchUpdatePshare($fileIds, $pshare);
                }
                break;

            case 'update_file_allowed_users':
                // 更新文件权限
                $fileId = $this->data['file_id'] ?? 0;
                if ($fileId > 0) {
                    ManticoreFile::updateAllowedUsers($fileId);
                }
                break;

            // ==============================
            // 用户同步动作
            // ==============================
            case 'user_sync':
                $user = User::find($this->data['userid'] ?? 0);
                if ($user) {
                    ManticoreUser::sync($user);
                }
                break;

            case 'user_delete':
                $userid = $this->data['userid'] ?? 0;
                if ($userid > 0) {
                    ManticoreUser::delete($userid);
                }
                break;

            // ==============================
            // 项目同步动作
            // ==============================
            case 'project_sync':
                $project = Project::find($this->data['id'] ?? 0);
                if ($project) {
                    ManticoreProject::sync($project);
                }
                break;

            case 'project_delete':
                $projectId = $this->data['project_id'] ?? 0;
                if ($projectId > 0) {
                    ManticoreProject::delete($projectId);
                }
                break;

            case 'update_project_allowed_users':
                // 更新项目权限
                $projectId = $this->data['project_id'] ?? 0;
                if ($projectId > 0) {
                    ManticoreProject::updateAllowedUsers($projectId);
                }
                break;

            case 'cascade_project_users':
                // 项目成员变更时，级联更新该项目下所有 visibility=1 的任务
                // 异步执行，避免阻塞
                $projectId = $this->data['project_id'] ?? 0;
                if ($projectId > 0) {
                    ManticoreTask::cascadeUpdateByProject($projectId);
                }
                break;

            // ==============================
            // 任务同步动作
            // ==============================
            case 'task_sync':
                $task = ProjectTask::find($this->data['id'] ?? 0);
                if ($task) {
                    ManticoreTask::sync($task);
                }
                break;

            case 'task_delete':
                $taskId = $this->data['task_id'] ?? 0;
                if ($taskId > 0) {
                    ManticoreTask::delete($taskId);
                }
                break;

            case 'update_task_allowed_users':
                // 更新任务权限
                $taskId = $this->data['task_id'] ?? 0;
                if ($taskId > 0) {
                    ManticoreTask::updateAllowedUsers($taskId);
                    // 级联更新子任务
                    ManticoreTask::cascadeToChildren($taskId);
                }
                break;

            // ==============================
            // 消息同步动作
            // ==============================
            case 'msg_sync':
                $msg = WebSocketDialogMsg::find($this->data['msg_id'] ?? 0);
                if ($msg) {
                    ManticoreMsg::sync($msg);
                }
                break;

            case 'msg_delete':
                $msgId = $this->data['msg_id'] ?? 0;
                if ($msgId > 0) {
                    ManticoreMsg::delete($msgId);
                }
                break;

            case 'update_dialog_allowed_users':
                // 更新对话消息权限
                $dialogId = $this->data['dialog_id'] ?? 0;
                if ($dialogId > 0) {
                    ManticoreMsg::updateDialogAllowedUsers($dialogId);
                }
                break;

            default:
                // 增量更新
                $this->incrementalUpdate();
                break;
        }
    }

    /**
     * 增量更新（定时执行 - 兜底机制）
     *
     * 命令本身会持续处理直到完成，定时器只是确保命令在运行
     * 如果命令正在运行（有锁），则跳过本次触发
     *
     * @return void
     */
    private function incrementalUpdate()
    {
        // 兜底触发：每 2 分钟检查一次，如果命令没在运行则启动
        $time = intval(Cache::get("ManticoreSyncTask:CheckTime"));
        if (time() - $time < 2 * 60) {
            return;
        }
        Cache::put("ManticoreSyncTask:CheckTime", time(), Carbon::now()->addMinutes(5));

        // 执行增量全文索引同步
        $this->runIncrementalSync();

        // 执行向量生成
        $this->runVectorGeneration();
    }

    /**
     * 执行增量全文索引同步（兜底触发）
     *
     * 命令内部有锁机制，如果已在运行会自动跳过
     * 命令会持续处理直到无新数据，然后自动退出
     */
    private function runIncrementalSync(): void
    {
        // 启动各类型的增量同步命令
        @shell_exec("php /var/www/artisan manticore:sync-files --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-users --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-projects --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-tasks --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-msgs --i 2>&1 &");

        // 启动失败重试命令
        @shell_exec("php /var/www/artisan manticore:retry-failures 2>&1 &");
    }

    /**
     * 执行向量生成（兜底触发）
     *
     * 命令内部有锁机制，如果已在运行会自动跳过
     * 命令会持续处理直到无待处理数据，然后自动退出
     */
    private function runVectorGeneration(): void
    {
        if (!Apps::isInstalled("ai")) {
            return;
        }

        // 启动向量生成命令
        @shell_exec("php /var/www/artisan manticore:generate-vectors --type=all --batch=50 2>&1 &");
    }

    public function end()
    {
    }
}
