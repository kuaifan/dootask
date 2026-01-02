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

    public function start()
    {
        if (!Apps::isInstalled("manticore")) {
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
                // 更新文件的 allowed_users（共享变更时调用）
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
                // 更新项目的 allowed_users（成员变更时调用）
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
                // 更新任务的 allowed_users（成员变更时调用）
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
                // 更新对话下所有消息的 allowed_users（成员变更时调用）
                $dialogId = $this->data['dialog_id'] ?? 0;
                if ($dialogId > 0) {
                    ManticoreMsg::updateDialogAllowedUsers($dialogId);
                }
                break;

            default:
                // 增量更新（定时任务调用）
                $this->incrementalUpdate();
                break;
        }
    }

    /**
     * 增量更新（定时执行）
     * 使用 --i 参数执行增量同步，会同步新增的向量数据
     *
     * @return void
     */
    private function incrementalUpdate()
    {
        // 60分钟执行一次
        $time = intval(Cache::get("ManticoreSyncTask:Time"));
        if (time() - $time < 60 * 60) {
            return;
        }

        // 执行开始
        Cache::put("ManticoreSyncTask:Time", time(), Carbon::now()->addMinutes(60));

        // 执行增量同步（MVA 方案不需要单独同步关系表）
        @shell_exec("php /var/www/artisan manticore:sync-files --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-users --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-projects --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-tasks --i 2>&1 &");
        @shell_exec("php /var/www/artisan manticore:sync-msgs --i 2>&1 &");

        // 执行完成
        Cache::put("ManticoreSyncTask:Time", time(), Carbon::now()->addMinutes(5));
    }

    public function end()
    {
    }
}
