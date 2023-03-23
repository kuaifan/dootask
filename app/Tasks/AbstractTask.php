<?php
namespace App\Tasks;

use App\Models\TaskWorker;
use App\Module\Base;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * Class AbstractTask
 * @package App\Tasks
 */
abstract class AbstractTask extends Task
{
    protected int $twid = 0;

    public function __construct(...$params)
    {
        $row = TaskWorker::createInstance([
            'args' => [
                'params' => $params,
                'class' => get_class($this)
            ],
        ]);
        if ($row->save()) {
            $this->twid = $row->id;
        }
    }

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
        TaskWorker::whereId($this->twid)->update(['start_at' => Carbon::now()]);
        //
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
        TaskWorker::whereId($this->twid)->update(['end_at' => Carbon::now()]);
        //
        try {
            $this->end();
            TaskWorker::whereId($this->twid)->delete();
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
        //
        TaskWorker::whereId($this->twid)->update(['error' => Base::array2json([
            'time' => Carbon::now(),
            'type' => $type,
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'message' => $e->getMessage(),
        ])]);
    }
}
