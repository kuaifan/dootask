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
            $startTime = Carbon::now();
            if ($hours > 0) {
                $endTime = Carbon::now()->addHours($hours);
                $taskLists1 = ProjectTask::whereNull('complete_at')
                    ->where('end_at', '>=', $startTime)
                    ->where('end_at', '<=', $endTime)
                    ->whereNull('archived_at')
                    ->take(100)
                    ->get()
                    ->toArray();
            }
            if ($hours2 > 0) {
                $endTime2 = Carbon::now()->subHours($hours2);
                $taskLists2 = ProjectTask::whereNull('complete_at')
                    ->where('end_at', '>=', $endTime2)
                    ->where('end_at', '<', $startTime)
                    ->whereNull('archived_at')
                    ->take(100)
                    ->get()
                    ->toArray();

            }
            $taskLists = array_merge($taskLists1, $taskLists2);
            $taskLists = Base::assoc_unique($taskLists, 'id');
            foreach ($taskLists as $task) {
                ProjectTask::overdueRemindEmail($task);
            }
        }
    }

}
