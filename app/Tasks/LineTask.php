<?php

namespace App\Tasks;

use App\Models\WebSocket;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/**
 * 上线、离线通知
 * Class LineTask
 * @package App\Tasks
 */
class LineTask extends AbstractTask
{
    protected $userid;
    protected $online;

    /**
     * LineTask constructor.
     * @param $userid
     * @param bool $online
     */
    public function __construct($userid, bool $online)
    {
        $this->userid = $userid;
        $this->online = $online;
    }

    public function start()
    {
        WebSocket::where('userid', '!=', $this->userid)->chunk(100, function ($list) {
            $fd = [];
            foreach ($list as $ws) {
                $fd[] = $ws->fd;
            }
            if ($fd) {
                PushTask::push([
                    'fd' => $fd,
                    'msg' => [
                        'type' => 'line',
                        'data' => [
                            'userid' => $this->userid,
                            'online' => $this->online,
                        ],
                    ]
                ]);
            }
        });
    }
}
