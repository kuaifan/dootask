<?php

namespace App\Observers;

use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Cache;

class AbstractObserver
{
    /**
     * 任务去重窗口时间（秒）
     * 同一个 action+id 在此时间内只投递一次
     */
    private const DEDUP_WINDOW = 10;

    /**
     * 投递异步任务（带去重）
     *
     * @param $task
     * @return void
     */
    public static function taskDeliver($task)
    {
        if (!app()->bound('swoole')) {
            return;
        }

        // 对 ManticoreSyncTask 进行去重
        if ($task instanceof \App\Tasks\ManticoreSyncTask) {
            $action = $task->getAction();
            $dataId = $task->getDataId();

            if ($action && $dataId) {
                $cacheKey = "manticore_task:{$action}:{$dataId}";

                // 如果已有相同任务在等待，跳过本次投递
                if (Cache::has($cacheKey)) {
                    return;
                }

                // 标记任务已投递
                Cache::put($cacheKey, true, self::DEDUP_WINDOW);
            }
        }

        Task::deliver($task);
    }
}
