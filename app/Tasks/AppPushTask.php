<?php

namespace App\Tasks;

use App\Models\ProjectTask;
use App\Module\Base;
use Carbon\Carbon;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class AppPushTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        $setting = Base::setting('appPushSetting');
        if ($setting['push'] !== 'open') {
            return;
        }
        ProjectTask::whereNull("complete_at")
            ->whereNull("archived_at")
            ->whereBetween("end_at", [
                Carbon::now()->addMinutes(60),
                Carbon::now()->addMinutes(60 + 10)
            ])->chunkById(100, function ($tasks) {
                /** @var ProjectTask $task */
                foreach ($tasks as $task) {
                    $task->taskPush(null, 1);
                }
            });
        ProjectTask::whereNull("complete_at")
            ->whereNull("archived_at")
            ->whereBetween("end_at", [
                Carbon::now()->subMinutes(60 + 10),
                Carbon::now()->subMinutes(60)
            ])->chunkById(100, function ($tasks) {
                /** @var ProjectTask $task */
                foreach ($tasks as $task) {
                    $task->taskPush(null, 2);
                }
            });
    }

    public function end()
    {

    }
}
