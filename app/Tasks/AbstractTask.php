<?php
namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * Class AbstractTask
 * @package App\Tasks
 */
abstract class AbstractTask extends Task
{
    protected $newTask = [];

    /**
     * 添加完成后执行的任务
     * @param $task
     */
    final protected function addTask($task)
    {
        $this->newTask[] = $task;
    }

    /**
     * 包装执行过程
     */
    final public function handle()
    {
        try {
            $this->start();
        } catch (\Exception $e) {
            $this->info($e);
            $this->failed($e);
        }
    }

    /**
     * 开始执行任务
     */
    abstract public function start();

    /**
     * 任务完成事件
     */
    public function finish()
    {
        foreach ($this->newTask AS $task) {
            Task::deliver($task);
        }
    }

    /**
     * 任务失败事件
     * @param $e
     */
    public function failed($e)
    {
        //
    }

    /**
     * 添加日志
     * @param $var
     */
    private function info($var)
    {
        if (!config('app.debug') || defined('DO_NOT_ADD_LOGS')) {
            return;
        }
        info($var);
    }
}
