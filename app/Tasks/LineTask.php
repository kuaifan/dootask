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
    protected $endPush = [];

    /**
     * LineTask constructor.
     * @param $userid
     * @param bool $online
     */
    public function __construct($userid, bool $online)
    {
        parent::__construct(...func_get_args());
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
                $this->endPush[] = [
                    'fd' => $fd,
                    'msg' => [
                        'type' => 'line',
                        'data' => [
                            'userid' => $this->userid,
                            'online' => $this->online,
                        ],
                    ]
                ];
            }
        });
    }

    public function end()
    {
        PushTask::push($this->endPush);
    }
}
