<?php

namespace App\Observers;

use Hhxsv5\LaravelS\Swoole\Task\Task;

class AbstractObserver
{
    /**
     * @param $task
     * @return void
     */
    public static function taskDeliver($task)
    {
        if (app()->bound('swoole')) {
            Task::deliver($task);
        }
    }
}
