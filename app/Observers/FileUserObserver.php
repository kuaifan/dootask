<?php

namespace App\Observers;

use App\Models\FileUser;
use App\Tasks\ManticoreSyncTask;

/**
 * FileUser 观察者
 */
class FileUserObserver extends AbstractObserver
{
    /**
     * Handle the FileUser "created" event.
     *
     * @param  \App\Models\FileUser  $fileUser
     * @return void
     */
    public function created(FileUser $fileUser)
    {
        // 更新文件权限
        self::taskDeliver(new ManticoreSyncTask('update_file_allowed_users', [
            'file_id' => $fileUser->file_id,
        ]));
    }

    /**
     * Handle the FileUser "updated" event.
     *
     * @param  \App\Models\FileUser  $fileUser
     * @return void
     */
    public function updated(FileUser $fileUser)
    {
        // 更新文件权限
        self::taskDeliver(new ManticoreSyncTask('update_file_allowed_users', [
            'file_id' => $fileUser->file_id,
        ]));
    }

    /**
     * Handle the FileUser "deleted" event.
     *
     * @param  \App\Models\FileUser  $fileUser
     * @return void
     */
    public function deleted(FileUser $fileUser)
    {
        // 更新文件权限
        self::taskDeliver(new ManticoreSyncTask('update_file_allowed_users', [
            'file_id' => $fileUser->file_id,
        ]));
    }
}
