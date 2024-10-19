<?php

namespace App\Tasks;

use App\Models\User;
use App\Module\Base;
use App\Models\Project;
use App\Models\ProjectTask;
use Carbon\Carbon;
use App\Models\WebSocketDialogMsg;
use Illuminate\Support\Facades\Cache;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class UnclaimedTaskRemindTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        //
        $setting = Base::setting('system');
        if ($setting['unclaimed_task_reminder'] !== 'open') {
            return;
        }
        if (!$setting['unclaimed_task_reminder_time']) {
            return;
        }
        //
        $times = explode(':', date('H:i'));
        $reminderTimes = explode(':', $setting['unclaimed_task_reminder_time']);
        if (!isset($times[1]) || !isset($reminderTimes[1]) || $times[0] != $reminderTimes[0]) {
            return;
        }
        // 执行一次
        if (Cache::get("UnclaimedTaskRemindTask:His", 0)) {
            return;
        }
        if ($times[1] >= intval($reminderTimes[1]) - 1 && $times[1] <= intval($reminderTimes[1]) + 1) {
            //
            Cache::put("UnclaimedTaskRemindTask:His", date('H:i:s'), Carbon::now()->addMinutes(5));
            //
            Project::whereNull('deleted_at')->whereNull('archived_at')->chunk(100, function ($projects) {
                foreach ($projects as $project) {
                    //
                    $projectTasks = ProjectTask::select(['project_tasks.id', 'project_tasks.name'])
                        ->leftJoin('project_task_users', function ($query) {
                            $query->on('project_tasks.id', '=', 'project_task_users.task_id');
                        })
                        ->where('project_tasks.project_id', $project->id)
                        ->whereNull('project_tasks.deleted_at')
                        ->whereNull('project_tasks.archived_at')
                        ->whereNull('project_task_users.id')
                        ->limit(10)
                        ->get();
                    //
                    if (!$projectTasks->isEmpty()) {
                        $botUser = User::botGetOrCreate('task-alert');
                        if (empty($botUser)) {
                            return;
                        }
                        WebSocketDialogMsg::sendMsg(null, $project->dialog_id, 'template', [
                            'type' => 'task_list',
                            'title' => '任务待领取',
                            'list' => $projectTasks->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'name' => $item->name,
                                ];
                            }),
                        ], $botUser->userid);
                    }
                }
            });
        }
    }

    public function end()
    {
    }
}
