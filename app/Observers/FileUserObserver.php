<?php

namespace App\Observers;

use App\Models\FileUser;
use App\Tasks\SeekDBSyncTask;

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
        self::taskDeliver(new SeekDBSyncTask('file_user_add', [
            'file_id' => $fileUser->file_id,
            'userid' => $fileUser->userid,
            'permission' => $fileUser->permission,
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
        self::taskDeliver(new SeekDBSyncTask('file_user_add', [
            'file_id' => $fileUser->file_id,
            'userid' => $fileUser->userid,
            'permission' => $fileUser->permission,
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
        self::taskDeliver(new SeekDBSyncTask('file_user_remove', [
            'file_id' => $fileUser->file_id,
            'userid' => $fileUser->userid,
        ]));
    }
}

