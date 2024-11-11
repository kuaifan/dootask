<?php

namespace App\Events;

use App\Models\WebSocket;
use App\Module\Base;
use Cache;
use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Swoole\Http\Server;

class WorkerStartEvent implements WorkerStartInterface
{

    public function __construct()
    {
    }

    public function handle(Server $server, $workerId)
    {
        // 仅在Worker进程启动时执行一次初始化代码
        $initTable = app('swoole')->initFlagTable;
        if ($initTable->incr('init_flag', 'value') === 1) {
            $this->handleFirstWorkerTasks();
        }
    }

    private function handleFirstWorkerTasks()
    {
        WebSocket::query()->delete();
        //
        $all = Base::json2array(Cache::get("User::online:all"));
        foreach ($all as $userid) {
            Cache::forget("User::online:" . $userid);
        }
    }
}
