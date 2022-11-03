<?php
namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * Class AbstractTask
 * @package App\Tasks
 */
abstract class AbstractTask extends Task
{

    /**
     * 开始执行任务
     */
    abstract public function start();

    /**
     * 任务完成事件
     */
    abstract public function end();

    /**
     * 重写执行过程
     */
    final public function handle()
    {
        try {
            $this->start();
        } catch (\Throwable $e) {
            $this->failed("start", $e);

        }
    }

    /**
     * 重写完成事件
     */
    final public function finish()
    {
        try {
            $this->end();
        } catch (\Throwable $e) {
            $this->failed("end", $e);
        }
    }

    /**
     * 任务失败事件
     * @param string $type
     * @param \Throwable $e
     */
    public function failed(string $type, \Throwable $e)
    {
        info($type);
        info($e);
    }
}
