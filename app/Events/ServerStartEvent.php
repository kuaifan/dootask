<?php


namespace App\Events;

use Hhxsv5\LaravelS\Swoole\Events\ServerStartInterface;
use Swoole\Http\Server;

class ServerStartEvent implements ServerStartInterface
{

    public function __construct()
    {
    }

    public function handle(Server $server)
    {
        $server->startMsecTime = $this->msecTime();
    }

    private function msecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $time = explode(".", $sec . ($msec * 1000));
        return $time[0];
    }
}
