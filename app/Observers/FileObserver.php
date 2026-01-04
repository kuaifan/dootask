<?php

namespace App\Observers;

use App\Models\File;
use App\Tasks\ManticoreSyncTask;

class FileObserver extends AbstractObserver
{
    /**
     * Handle the File "created" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function created(File $file)
    {
        // 文件夹不需要同步
        if ($file->type === 'folder') {
            return;
        }
        self::taskDeliver(new ManticoreSyncTask('file_sync', $file->toArray()));
    }

    /**
     * Handle the File "updated" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function updated(File $file)
    {
        // 检查共享设置是否变化（影响子文件的 pshare）
        if ($file->type === 'folder' && $file->isDirty('share')) {
            // 共享文件夹的 share 字段变化，需要批量更新子文件的 pshare
            // 注意：updateShare 方法会批量更新，但不会触发 Observer
            $newPshare = $file->share ? $file->id : 0;
            $childFileIds = File::where('pids', 'like', "%,{$file->id},%")
                ->where('type', '!=', 'folder')
                ->pluck('id')
                ->toArray();
            if (!empty($childFileIds)) {
                self::taskDeliver(new ManticoreSyncTask('file_pshare_update', [
                    'file_ids' => $childFileIds,
                    'pshare' => $newPshare,
                ]));
            }
            return;
        }

        // 文件夹不需要同步内容
        if ($file->type === 'folder') {
            return;
        }
        self::taskDeliver(new ManticoreSyncTask('file_sync', $file->toArray()));
    }

    /**
     * Handle the File "deleted" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function deleted(File $file)
    {
        self::taskDeliver(new ManticoreSyncTask('file_delete', $file->toArray()));
    }

    /**
     * Handle the File "restored" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function restored(File $file)
    {
        // 文件夹不需要同步
        if ($file->type === 'folder') {
            return;
        }
        self::taskDeliver(new ManticoreSyncTask('file_sync', $file->toArray()));
    }

    /**
     * Handle the File "force deleted" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function forceDeleted(File $file)
    {
        self::taskDeliver(new ManticoreSyncTask('file_delete', $file->toArray()));
    }
}

