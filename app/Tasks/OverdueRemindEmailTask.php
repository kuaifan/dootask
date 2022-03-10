<?php


namespace App\Tasks;


use App\Models\ProjectTask;
use App\Module\Base;
use Carbon\Carbon;

class OverdueRemindEmailTask extends AbstractTask
{
    public function __construct()
    {
        //
    }

    public function start()
    {
        $setting = Base::setting('emailSetting');
        if ($setting['notice'] === 'open') {
            $hours = floatval($setting['task_remind_hours']);
            $hours2 = floatval($setting['task_remind_hours2']);
            $taskLists1 = [];
            $taskLists2 = [];
            if ($hours > 0) {
                $taskLists1 = ProjectTask::whereNull('complete_at')
                    ->where('end_at', '>=', Carbon::now()->addMinutes($hours * 60 - 3)->rawFormat('Y-m-d H:i:s'))
                    ->where('end_at', '<=', Carbon::now()->addMinutes($hours * 60 + 3)->rawFormat('Y-m-d H:i:s'))
                    ->whereNull('archived_at')
                    ->take(100)
                    ->get()
                    ->toArray();
            }
            if ($hours2 > 0) {
                $taskLists2 = ProjectTask::whereNull('complete_at')
                    ->where('end_at', '>=', Carbon::now()->subMinutes($hours2 * 60 + 3)->rawFormat('Y-m-d H:i:s'))
                    ->where('end_at', '<=', Carbon::now()->subMinutes($hours2 * 60 - 3)->rawFormat('Y-m-d H:i:s'))
                    ->whereNull('archived_at')
                    ->take(100)
                    ->get()
                    ->toArray();
            }
            $taskLists = array_merge($taskLists1, $taskLists2);
            $ids = [];
            foreach ($taskLists as $task) {
                if (!in_array($task->id, $ids)) {
                    $ids[] = $task->id;
                    ProjectTask::overdueRemindEmail($task);
                }
            }
        }
    }

}
