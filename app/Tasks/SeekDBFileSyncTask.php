<?php

namespace App\Tasks;

use App\Models\File;
use App\Module\Apps;
use App\Module\SeekDB\SeekDBFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 同步文件数据到 SeekDB
 */
class SeekDBFileSyncTask extends AbstractTask
{
    private $action;

    private $data;

    public function __construct($action = null, $data = null)
    {
        parent::__construct(...func_get_args());
        $this->action = $action;
        $this->data = $data;
    }

    public function start()
    {
        if (!Apps::isInstalled("seekdb")) {
            // 如果没有安装 SeekDB 模块，则不执行
            return;
        }

        switch ($this->action) {
            case 'sync':
                // 同步文件数据
                $file = File::find($this->data['id'] ?? 0);
                if ($file) {
                    SeekDBFile::sync($file);
                }
                break;

            case 'delete':
                // 删除文件索引
                $fileId = $this->data['id'] ?? 0;
                if ($fileId > 0) {
                    SeekDBFile::delete($fileId);
                }
                break;

            case 'sync_file_user':
                // 同步文件用户关系
                $fileId = $this->data['file_id'] ?? 0;
                if ($fileId > 0) {
                    SeekDBFile::syncFileUsers($fileId);
                }
                break;

            case 'add_file_user':
                // 添加文件用户关系
                $fileId = $this->data['file_id'] ?? 0;
                $userid = $this->data['userid'] ?? 0;
                $permission = $this->data['permission'] ?? 0;
                if ($fileId > 0) {
                    SeekDBFile::addFileUser($fileId, $userid, $permission);
                }
                break;

            case 'remove_file_user':
                // 删除文件用户关系
                $fileId = $this->data['file_id'] ?? 0;
                $userid = $this->data['userid'] ?? null;
                if ($fileId > 0) {
                    SeekDBFile::removeFileUser($fileId, $userid);
                }
                break;

            case 'update_pshare':
                // 批量更新文件的 pshare（共享设置变化时调用）
                $fileIds = $this->data['file_ids'] ?? [];
                $pshare = $this->data['pshare'] ?? 0;
                if (!empty($fileIds)) {
                    \App\Module\SeekDB\SeekDBBase::batchUpdatePshare($fileIds, $pshare);
                }
                break;

            default:
                // 增量更新
                $this->incrementalUpdate();
                break;
        }
    }

    /**
     * 增量更新
     * @return void
     */
    private function incrementalUpdate()
    {
        // 120分钟执行一次
        $time = intval(Cache::get("SeekDBFileSyncTask:Time"));
        if (time() - $time < 120 * 60) {
            return;
        }

        // 执行开始，120分钟后缓存标记失效
        Cache::put("SeekDBFileSyncTask:Time", time(), Carbon::now()->addMinutes(120));

        // 开始执行同步
        @shell_exec("php /var/www/artisan seekdb:sync-files --i");

        // 执行完成，5分钟后缓存标记失效（5分钟任务可重复执行）
        Cache::put("SeekDBFileSyncTask:Time", time(), Carbon::now()->addMinutes(5));
    }

    public function end()
    {
    }
}

