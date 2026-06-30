<?php

namespace App\Tasks;

use App\Models\File;
use App\Models\TaskWorker;
use App\Models\Tmp;
use App\Models\UserDevice;
use App\Models\UmengLog;
use App\Models\WebSocketTmpMsg;
use App\Module\Base;
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
            case 'tmp_msgs':
                WebSocketTmpMsg::where('created_at', '<', Carbon::now()->subHours($this->hours))
                    ->orderBy('id')
                    ->chunk(500, function ($msgs) {
                        /** @var WebSocketTmpMsg $msg */
                        foreach ($msgs as $msg) {
                            $msg->delete();
                        }
                    });
                break;

            case 'tmp':
                Tmp::where('created_at', '<', Carbon::now()->subHours($this->hours))
                    ->orderBy('id')
                    ->chunk(500, function ($tmps) {
                        /** @var Tmp $tmp */
                        foreach ($tmps as $tmp) {
                            $tmp->delete();
                        }
                    });
                break;

            case 'task_worker':
                TaskWorker::onlyTrashed()
                    ->where('deleted_at', '<', Carbon::now()->subHours($this->hours))
                    ->orderBy('id')
                    ->forceDelete();
                break;

            case 'file':
                $day = intval(config('dootask.auto_empty_file_recycle'));
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
                break;

            case 'tmp_file':
                $day = intval(config('dootask.auto_empty_temp_file'));
                if ($day <= 0) {
                    return;
                }
                $files = Base::recursiveFiles(public_path('uploads/tmp'));
                foreach ($files as $file) {
                    $time = @filemtime($file);
                    if ($time && $time < time() - 3600 * 24 * $day) {
                        unlink($file);
                    }
                }
                break;

            case 'tmp_chunks':
                // 分片上传残留：upload_id 目录超过 hours 小时未合并则整目录清掉
                $chunksRoot = public_path('uploads/tmp/chunks');
                if (!is_dir($chunksRoot)) {
                    break;
                }
                $cutoff = time() - 3600 * $this->hours;
                foreach (glob($chunksRoot . '/*', GLOB_ONLYDIR) ?: [] as $userDir) {
                    foreach (glob($userDir . '/*', GLOB_ONLYDIR) ?: [] as $uploadDir) {
                        $mtime = @filemtime($uploadDir);
                        if ($mtime && $mtime < $cutoff) {
                            Base::deleteDirAndFile($uploadDir);
                        }
                    }
                    // 顺手清理空 user 目录
                    if (count(scandir($userDir) ?: []) <= 2) {
                        @rmdir($userDir);
                    }
                }
                break;

            case 'user_device':
                UserDevice::where('expired_at', '<', Carbon::now()->subHours($this->hours))
                    ->orderBy('id')
                    ->chunk(500, function ($devices) {
                        /** @var UserDevice $device */
                        foreach ($devices as $device) {
                            UserDevice::forget($device);
                        }
                    });
                break;

            case 'umeng_log':
                UmengLog::where('created_at', '<', Carbon::now()->subHours($this->hours))
                    ->orderBy('id')
                    ->chunk(500, function ($logs) {
                        /** @var UmengLog $log */
                        foreach ($logs as $log) {
                            $log->delete();
                        }
                    });
                break;
        }
    }

    public function end()
    {

    }
}
