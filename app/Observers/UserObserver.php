<?php

namespace App\Observers;

use App\Models\User;
use App\Tasks\ManticoreSyncTask;

class UserObserver extends AbstractObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        // 机器人账号不同步
        if ($user->bot) {
            return;
        }
        self::taskDeliver(new ManticoreSyncTask('user_sync', $user->toArray()));
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        // 机器人账号不同步
        if ($user->bot) {
            return;
        }

        // 检查是否有搜索相关字段变化
        $searchableFields = ['nickname', 'email', 'tel', 'profession', 'introduction', 'disable_at'];
        $isDirty = false;
        foreach ($searchableFields as $field) {
            if ($user->isDirty($field)) {
                $isDirty = true;
                break;
            }
        }

        if ($isDirty) {
            // 如果用户被禁用，删除索引；否则更新索引
            if ($user->disable_at) {
                self::taskDeliver(new ManticoreSyncTask('user_delete', ['userid' => $user->userid]));
            } else {
                self::taskDeliver(new ManticoreSyncTask('user_sync', $user->toArray()));
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        self::taskDeliver(new ManticoreSyncTask('user_delete', ['userid' => $user->userid]));
    }
}

