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
                $time1 = Carbon::now()->addMinutes($hours * 60);
                $taskLists1 = ProjectTask::whereNull('complete_at')
                    ->where('end_at', '>=', $time1->subMinutes(2))
                    ->where('end_at', '<=', $time1->addMinutes(2))
                    ->whereNull('archived_at')
                    ->take(100)
                    ->get()
                    ->toArray();
            }
            if ($hours2 > 0) {
                $time2 = Carbon::now()->subMinutes($hours2 * 60);
                $taskLists2 = ProjectTask::whereNull('complete_at')
                    ->where('end_at', '>=', $time2->subMinutes(2))
                    ->where('end_at', '<=', $time2->addMinutes(2))
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
