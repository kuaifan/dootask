<?php

namespace App\Observers;

use App\Models\User;
use App\Module\Apps;
use App\Tasks\ManticoreSyncTask;

class UserObserver extends AbstractObserver
{
    /**
     * 搜索相关字段（Manticore 同步）
     */
    private static array $searchableFields = [
        'nickname', 'email', 'profession', 'introduction', 'disable_at'
    ];

    /**
     * 需要监控并触发 user_update hook 的字段
     */
    private static array $hookMonitoredFields = [
        'email', 'tel', 'nickname', 'profession',
        'birthday', 'address', 'introduction', 'department'
    ];

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
        // 机器人账号不处理
        if ($user->bot) {
            return;
        }

        // 检查是否有搜索相关字段变化（Manticore 同步）
        $isDirty = false;
        foreach (self::$searchableFields as $field) {
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

        // 检测 onboard/offboard 场景（disable_at 变化）
        if ($user->isDirty('disable_at')) {
            $originalDisableAt = $user->getOriginal('disable_at');
            $currentDisableAt = $user->disable_at;

            if ($originalDisableAt && !$currentDisableAt) {
                // disable_at 从有值变为 null → 取消离职 (restore)
                Apps::dispatchUserHook($user, 'user_onboard', 'restore');
            } elseif (!$originalDisableAt && $currentDisableAt) {
                // disable_at 从 null 变为有值 → 离职 (offboarded)
                Apps::dispatchUserHook($user, 'user_offboard', 'offboarded');
            }
            return;
        }

        // 排除仅 identity 变化的场景
        if ($user->isDirty('identity')) {
            return;
        }

        // 检测监控字段变更，触发 user_update hook
        $changedFields = [];
        foreach (self::$hookMonitoredFields as $field) {
            if ($user->isDirty($field)) {
                $changedFields[] = $field;
            }
        }

        if (!empty($changedFields)) {
            // 判断是用户自己修改还是管理员修改
            $currentUser = User::authInfo();
            $eventType = ($currentUser && $currentUser->userid === $user->userid)
                ? 'profile_update'
                : 'admin_update';
            Apps::dispatchUserHook($user, 'user_update', $eventType, $changedFields);
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
        // Manticore 索引删除
        self::taskDeliver(new ManticoreSyncTask('user_delete', ['userid' => $user->userid]));

        // 触发 user_offboard (delete) hook
        if (!$user->bot) {
            Apps::dispatchUserHook($user, 'user_offboard', 'delete');
        }
    }
}

