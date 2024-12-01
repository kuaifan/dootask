<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\ProjectTask;
use App\Module\Base;
use Carbon\Carbon;

/**
 * 完成的任务自动归档
 * Class AutoArchivedTask
 * @package App\Tasks
 */
class AutoArchivedTask extends AbstractTask
{

    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        $this->systemAutoArchived();
        $this->projectAutoArchived();
    }

    /**
     * 处理已完成未归档的任务（系统默认）
     */
    private function systemAutoArchived()
    {
        $setting = Base::setting('system');
        if ($setting['auto_archived'] !== 'open') {
            return;
        }
        $archivedDay = min(365, floatval($setting['archived_day']));
        if ($archivedDay <= 0) {
            return;
        }
        $taskLists = ProjectTask::select('project_tasks.*')
            ->join('projects', 'projects.id', '=', 'project_tasks.project_id')
            ->whereNotNull('project_tasks.complete_at')
            ->where('project_tasks.complete_at', '<=', Carbon::now()->subDays($archivedDay))
            ->where('project_tasks.archived_userid', 0)
            ->whereNull('project_tasks.archived_at')
            ->where('projects.archive_method', '!=', 'custom')
            ->take(100)
            ->get();
        /** @var ProjectTask $task */
        foreach ($taskLists as $task) {
            $task->archivedTask(Carbon::now(), true);
        }
    }

    /**
     * 处理已完成未归档的任务（项目自定义）
     */
    private function projectAutoArchived()
    {
        // 获取设置了自定义归档的项目的任务
        $prefix = \DB::getTablePrefix();
        $taskLists = ProjectTask::select('project_tasks.*')
            ->join('projects', 'projects.id', '=', 'project_tasks.project_id')
            ->whereNotNull('project_tasks.complete_at')
            ->where('project_tasks.archived_userid', 0)
            ->whereNull('project_tasks.archived_at')
            ->where('projects.archive_method', 'custom')
            ->whereRaw("DATEDIFF(NOW(), {$prefix}project_tasks.complete_at) >= {$prefix}projects.archive_days")
            ->with(['project' => function ($query) {
                $query->select('id', 'archive_days');
            }])
            ->take(100)
            ->get();

        /** @var ProjectTask $task */
        foreach ($taskLists as $task) {
            $task->archivedTask(Carbon::now(), true);
        }
    }

    public function end()
    {

    }
}
