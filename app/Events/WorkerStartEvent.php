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
        if (isset($server->startMsecTime) && Cache::get("swooleServerStartMsecTime") != $server->startMsecTime) {
            Cache::forever("swooleServerStartMsecTime", $server->startMsecTime);
            WebSocket::query()->delete();
            //
            $all = Base::json2array(Cache::get("User::online:all"));
            foreach ($all as $userid) {
                Cache::forget("User::online:" . $userid);
            }
        }
    }
}
