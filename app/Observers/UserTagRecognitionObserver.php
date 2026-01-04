<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserTag;
use App\Models\UserTagRecognition;
use App\Tasks\ManticoreSyncTask;

class UserTagRecognitionObserver extends AbstractObserver
{
    /**
     * Handle the UserTagRecognition "created" event.
     * 认可创建时，标签排序可能变化，触发用户索引更新
     *
     * @param  \App\Models\UserTagRecognition  $recognition
     * @return void
     */
    public function created(UserTagRecognition $recognition)
    {
        $this->syncUserByTagId($recognition->tag_id);
    }

    /**
     * Handle the UserTagRecognition "deleted" event.
     * 认可删除时，标签排序可能变化，触发用户索引更新
     *
     * @param  \App\Models\UserTagRecognition  $recognition
     * @return void
     */
    public function deleted(UserTagRecognition $recognition)
    {
        $this->syncUserByTagId($recognition->tag_id);
    }

    /**
     * 根据标签ID触发用户同步
     *
     * @param int $tagId 标签ID
     * @return void
     */
    private function syncUserByTagId(int $tagId)
    {
        if ($tagId <= 0) {
            return;
        }

        $tag = UserTag::find($tagId);
        if (!$tag) {
            return;
        }

        $user = User::find($tag->user_id);
        if (!$user || $user->bot || $user->disable_at) {
            return;
        }

        self::taskDeliver(new ManticoreSyncTask('user_sync', $user->toArray()));
    }
}
