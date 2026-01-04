<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserTag;
use App\Tasks\ManticoreSyncTask;

class UserTagObserver extends AbstractObserver
{
    /**
     * Handle the UserTag "created" event.
     * 标签创建时，触发用户索引更新
     *
     * @param  \App\Models\UserTag  $userTag
     * @return void
     */
    public function created(UserTag $userTag)
    {
        $this->syncUserToManticore($userTag->user_id);
    }

    /**
     * Handle the UserTag "updated" event.
     * 标签更新时，触发用户索引更新
     *
     * @param  \App\Models\UserTag  $userTag
     * @return void
     */
    public function updated(UserTag $userTag)
    {
        // 只有标签名称变化时才需要更新
        if ($userTag->isDirty('name')) {
            $this->syncUserToManticore($userTag->user_id);
        }
    }

    /**
     * Handle the UserTag "deleted" event.
     * 标签删除时，触发用户索引更新
     *
     * @param  \App\Models\UserTag  $userTag
     * @return void
     */
    public function deleted(UserTag $userTag)
    {
        $this->syncUserToManticore($userTag->user_id);
    }

    /**
     * 触发用户同步到 Manticore
     *
     * @param int $userid 用户ID
     * @return void
     */
    private function syncUserToManticore(int $userid)
    {
        if ($userid <= 0) {
            return;
        }

        $user = User::find($userid);
        if (!$user || $user->bot || $user->disable_at) {
            return;
        }

        self::taskDeliver(new ManticoreSyncTask('user_sync', $user->toArray()));
    }
}
