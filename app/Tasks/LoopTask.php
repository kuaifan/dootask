<?php

namespace App\Tasks;

use App\Models\ProjectTask;
use Carbon\Carbon;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/**
 * 任务重复周期
 */
class LoopTask extends AbstractTask
{
    public function __construct()
    {

    }

    public function start()
    {
        ProjectTask::whereBetween('loop_at', [
            Carbon::now()->subMinutes(10),
            Carbon::now()
        ])->chunkById(100, function ($list) {
            /** @var ProjectTask $item */
            foreach ($list as $item) {
                try {
                    $task = $item->copyTask();
                    if ($item->start_at) {
                        $diffSecond = Carbon::parse($item->start_at)->diffInSeconds(Carbon::parse($item->end_at), true);
                        $task->start_at = Carbon::parse($item->loop_at);
                        $task->end_at = $task->start_at->addSeconds($diffSecond);
                    }
                    $task->refreshLoop(true);
                    $task->addLog("创建任务来自周期任务ID：" . $item->id, [], $item->userid);
                    //
                    $item->loop = '';
                    $item->loop_at = null;
                    $item->save();
                } catch (\Throwable $e) {
                    $item->addLog("生成重复任务失败：" . $e->getMessage(), [], $item->userid);
                }
            }
        });
    }
}
