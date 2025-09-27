<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\UserTaskBrowse
 *
 * @property int $id
 * @property int|null $userid 用户ID
 * @property int|null $task_id 任务ID
 * @property \Illuminate\Support\Carbon|null $browsed_at 浏览时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectTask|null $task
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse whereBrowsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTaskBrowse whereUserid($value)
 * @mixin \Eloquent
 */
class UserTaskBrowse extends AbstractModel
{
    protected $fillable = [
        'userid',
        'task_id', 
        'browsed_at',
    ];

    protected $dates = [
        'browsed_at',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'task_id', 'id');
    }

    /**
     * 记录用户浏览任务
     * @param int $userid 用户ID
     * @param int $task_id 任务ID
     * @return UserTaskBrowse
     */
    public static function recordBrowse($userid, $task_id)
    {
        $record = self::updateOrCreate(
            [
                'userid' => $userid,
                'task_id' => $task_id,
            ],
            [
                'browsed_at' => Carbon::now(),
            ]
        );

        UserRecentItem::record(
            $userid,
            UserRecentItem::TYPE_TASK,
            $task_id,
            UserRecentItem::SOURCE_PROJECT,
            0
        );

        return $record;
    }

    /**
     * 获取用户浏览历史
     * @param int $userid 用户ID  
     * @param int $limit 获取数量
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserBrowseHistory($userid, $limit = 20)
    {
        return self::with(['task' => function ($query) {
                $query->select([
                    'id', 'name', 'project_id', 'column_id', 'parent_id',
                    'flow_item_id', 'flow_item_name', 
                    'complete_at', 'archived_at'
                ]);
            }])
            ->whereUserid($userid)
            ->whereHas('task', function ($query) {
                // 只获取存在且未被删除的任务
                $query->whereNull('archived_at');
            })
            ->orderByDesc('browsed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 清理用户浏览历史
     * @param int $userid 用户ID
     * @param int $keepCount 保留数量，0表示全部删除
     * @return int 删除的记录数
     */
    public static function cleanUserBrowseHistory($userid, $keepCount = 100)
    {
        if ($keepCount === 0) {
            return self::whereUserid($userid)->delete();
        }

        $keepIds = self::whereUserid($userid)
            ->orderByDesc('browsed_at')
            ->limit($keepCount)
            ->pluck('id');

        return self::whereUserid($userid)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
