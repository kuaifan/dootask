<?php

namespace App\Tasks;

use App\Models\File;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Module\Apps;
use App\Module\SeekDB\SeekDBBase;
use App\Module\SeekDB\SeekDBFile;
use App\Module\SeekDB\SeekDBUser;
use App\Module\SeekDB\SeekDBProject;
use App\Module\SeekDB\SeekDBTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 通用 SeekDB 同步任务
 *
 * 支持文件、用户、项目、任务的同步操作
 */
class SeekDBSyncTask extends AbstractTask
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
        if (!Apps::isInstalled("seekdb")) {
            return;
        }

        switch ($this->action) {
            // ==============================
            // 文件同步动作
            // ==============================
            case 'file_sync':
                $file = File::find($this->data['id'] ?? 0);
                if ($file) {
                    SeekDBFile::sync($file);
                }
                break;

            case 'file_delete':
                $fileId = $this->data['id'] ?? 0;
                if ($fileId > 0) {
                    SeekDBFile::delete($fileId);
                }
                break;

            case 'file_user_sync':
                $fileId = $this->data['file_id'] ?? 0;
                if ($fileId > 0) {
                    SeekDBFile::syncFileUsers($fileId);
                }
                break;

            case 'file_user_add':
                $fileId = $this->data['file_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                $permission = $this->data['permission'] ?? 0;
                if ($fileId > 0) {
                    SeekDBFile::addFileUser($fileId, $userid, $permission);
                }
                break;

            case 'file_user_remove':
                $fileId = $this->data['file_id'] ?? 0;
                $userid = $this->data['userid'] ?? null;
                if ($fileId > 0) {
                    SeekDBFile::removeFileUser($fileId, $userid);
                }
                break;

            case 'file_pshare_update':
                $fileIds = $this->data['file_ids'] ?? [];
                $pshare = $this->data['pshare'] ?? 0;
                if (!empty($fileIds)) {
                    SeekDBBase::batchUpdatePshare($fileIds, $pshare);
                }
                break;

            // ==============================
            // 用户同步动作
            // ==============================
            case 'user_sync':
                $user = User::find($this->data['userid'] ?? 0);
                if ($user) {
                    SeekDBUser::sync($user);
                }
                break;

            case 'user_delete':
                $userid = $this->data['userid'] ?? 0;
                if ($userid > 0) {
                    SeekDBUser::delete($userid);
                }
                break;

            // ==============================
            // 项目同步动作
            // ==============================
            case 'project_sync':
                $project = Project::find($this->data['id'] ?? 0);
                if ($project) {
                    SeekDBProject::sync($project);
                }
                break;

            case 'project_delete':
                $projectId = $this->data['project_id'] ?? 0;
                if ($projectId > 0) {
                    SeekDBProject::delete($projectId);
                }
                break;

            case 'project_user_add':
                $projectId = $this->data['project_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                if ($projectId > 0 && $userid > 0) {
                    SeekDBProject::addProjectUser($projectId, $userid);
                }
                break;

            case 'project_user_remove':
                $projectId = $this->data['project_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                if ($projectId > 0 && $userid > 0) {
                    SeekDBProject::removeProjectUser($projectId, $userid);
                }
                break;

            case 'project_users_sync':
                $projectId = $this->data['project_id'] ?? 0;
                if ($projectId > 0) {
                    SeekDBProject::syncProjectUsers($projectId);
                }
                break;

            // ==============================
            // 任务同步动作
            // ==============================
            case 'task_sync':
                $task = ProjectTask::find($this->data['id'] ?? 0);
                if ($task) {
                    SeekDBTask::sync($task);
                }
                break;

            case 'task_delete':
                $taskId = $this->data['task_id'] ?? 0;
                if ($taskId > 0) {
                    SeekDBTask::delete($taskId);
                }
                break;

            case 'task_visibility_update':
                $taskId = $this->data['task_id'] ?? 0;
                $visibility = $this->data['visibility'] ?? 1;
                if ($taskId > 0) {
                    SeekDBTask::updateVisibility($taskId, $visibility);
                }
                break;

            case 'task_user_add':
                $taskId = $this->data['task_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                if ($taskId > 0 && $userid > 0) {
                    SeekDBTask::addTaskUser($taskId, $userid);
                }
                break;

            case 'task_user_remove':
                $taskId = $this->data['task_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                if ($taskId > 0 && $userid > 0) {
                    SeekDBTask::removeTaskUser($taskId, $userid);
                }
                break;

            case 'task_visibility_user_remove':
                // 特殊处理：删除 visibility user 时需要检查是否仍是任务成员
                $taskId = $this->data['task_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                if ($taskId > 0 && $userid > 0) {
                    SeekDBTask::removeVisibilityUser($taskId, $userid);
                }
                break;

            case 'task_users_sync':
                $taskId = $this->data['task_id'] ?? 0;
                if ($taskId > 0) {
                    SeekDBTask::syncTaskUsers($taskId);
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
     * 使用 --i 参数执行增量同步，会同步新增的向量数据和用户关系数据
     *
     * @return void
     */
    private function incrementalUpdate()
    {
        // 60分钟执行一次
        $time = intval(Cache::get("SeekDBSyncTask:Time"));
        if (time() - $time < 60 * 60) {
            return;
        }

        // 执行开始
        Cache::put("SeekDBSyncTask:Time", time(), Carbon::now()->addMinutes(60));

        // 执行增量同步（同时同步向量表和用户关系表的新增数据）
        @shell_exec("php /var/www/artisan seekdb:sync-files --i 2>&1 &");
        @shell_exec("php /var/www/artisan seekdb:sync-users --i 2>&1 &");
        @shell_exec("php /var/www/artisan seekdb:sync-projects --i 2>&1 &");
        @shell_exec("php /var/www/artisan seekdb:sync-tasks --i 2>&1 &");

        // 执行完成
        Cache::put("SeekDBSyncTask:Time", time(), Carbon::now()->addMinutes(5));
    }

    public function end()
    {
    }
}

