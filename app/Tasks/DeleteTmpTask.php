<?php

namespace App\Tasks;

use App\Models\Tmp;
use App\Models\WebSocketTmpMsg;
use Carbon\Carbon;

/**
 * 删除过期临时数据任务
 * Class DeleteTmpTask
 * @package App\Tasks
 */
class DeleteTmpTask extends AbstractTask
{
    protected $data;
    protected $hours; // 多久后删除，单位小时

    public function __construct(string $data, int $hours)
    {
        $this->data = $data;
        $this->hours = $hours;
    }

    public function start()
    {
        switch ($this->data) {
            /**
             * 表pre_wg_tmp_msgs
             */
            case 'wg_tmp_msgs':
                {
                    WebSocketTmpMsg::where('created_at', '<', Carbon::now()->subHours($this->hours)->toDateTimeString())
                        ->orderBy('id')
                        ->chunk(500, function ($msgs) {
                            foreach ($msgs as $msg) {
                                $msg->delete();
                            }
                        });
                }
                break;

            /**
             * 表pre_wg_tmp
             */
            case 'tmp':
                {
                    Tmp::where('created_at', '<', Carbon::now()->subHours($this->hours)->toDateTimeString())
                        ->orderBy('id')
                        ->chunk(2000, function ($tmps) {
                            foreach ($tmps as $tmp) {
                                $tmp->delete();
                            }
                        });
                }
                break;
        }
    }
}
