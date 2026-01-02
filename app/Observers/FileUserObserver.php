<?php

namespace App\Observers;

use App\Models\FileUser;
use App\Tasks\ManticoreSyncTask;

/**
 * FileUser 观察者（MVA 权限方案）
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
        // MVA 方案：更新文件的 allowed_users
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
        // MVA 方案：更新文件的 allowed_users
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
        // MVA 方案：更新文件的 allowed_users
        self::taskDeliver(new ManticoreSyncTask('update_file_allowed_users', [
            'file_id' => $fileUser->file_id,
        ]));
    }
}
