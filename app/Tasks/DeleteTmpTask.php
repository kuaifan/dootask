<?php

namespace App\Tasks;

use App\Models\File;
use App\Models\TaskWorker;
use App\Models\Tmp;
use App\Models\WebSocketTmpMsg;
use Carbon\Carbon;
use Illuminate\Support\Facades\File as SupportFile;

/**
 * 删除过期临时数据任务
 * Class DeleteTmpTask
 * @package App\Tasks
 */
class DeleteTmpTask extends AbstractTask
{
    protected $data;
    protected $hours; // 多久后删除，单位小时

    /**
     * @param string $data
     * @param int $hours
     */
    public function __construct(string $data, int $hours = 24)
    {
        parent::__construct(...func_get_args());
        $this->data = $data;
        $this->hours = $hours;
    }

    public function start()
    {
        switch ($this->data) {
            /**
             * 表pre_tmp_msgs
             */
            case 'wg_tmp_msgs':
                {
                    WebSocketTmpMsg::where('created_at', '<', Carbon::now()->subHours($this->hours))
                        ->orderBy('id')
                        ->chunk(500, function ($msgs) {
                            /** @var WebSocketTmpMsg $msg */
                            foreach ($msgs as $msg) {
                                $msg->delete();
                            }
                        });
                }
                break;

            /**
             * 表pre_tmp
             */
            case 'tmp':
                {
                    Tmp::where('created_at', '<', Carbon::now()->subHours($this->hours))
                        ->orderBy('id')
                        ->chunk(500, function ($tmps) {
                            /** @var Tmp $tmp */
                            foreach ($tmps as $tmp) {
                                $tmp->delete();
                            }
                        });
                }
                break;

            /**
             * 表pre_task_worker
             */
            case 'task_worker':
                {
                    TaskWorker::onlyTrashed()
                        ->where('deleted_at', '<', Carbon::now()->subHours($this->hours))
                        ->orderBy('id')
                        ->forceDelete();
                }
                break;

            /**
             * 表pre_file
             */
            case 'file':
                {
                    $day = intval(env("AUTO_EMPTY_FILE_RECYCLE", 365));
                    if ($day <= 0) {
                        return;
                    }
                    File::onlyTrashed()
                        ->where('deleted_at', '<', Carbon::now()->subHours($day))
                        ->orderBy('id')
                        ->chunk(500, function ($files) {
                            /** @var File $file */
                            foreach ($files as $file) {
                                $file->forceDeleteFile();
                            }
                        });
                }
                break;

            /**
             * file_pack 临时压缩下载文件
             */
            case 'file_pack':
                {
                    $path = public_path('tmp/file/');
                    if (!SupportFile::exists($path)) {
                        return;
                    }
                    $dirIterator = new \RecursiveDirectoryIterator($path);
                    $iterator = new \RecursiveIteratorIterator($dirIterator);
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $time = $file->getMTime();
                            if ($time < time() - 3600 * 24) {
                                unlink($file->getPathname());
                            }
                        }
                    }
                }
                break;
        }
    }

    public function end()
    {

    }
}
