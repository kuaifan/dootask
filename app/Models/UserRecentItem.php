<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\UserRecentItem
 *
 * @property int $id
 * @property int $userid 用户ID
 * @property string $target_type 目标类型(task/file/task_file/message_file 等)
 * @property int $target_id 目标ID
 * @property string $source_type 来源类型(project/filesystem/project_task/dialog 等)
 * @property int $source_id 来源ID
 * @property \Illuminate\Support\Carbon|null $browsed_at 浏览时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereBrowsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserRecentItem whereUserid($value)
 * @mixin \Eloquent
 */
class UserRecentItem extends AbstractModel
{
    public const TYPE_TASK = 'task';
    public const TYPE_FILE = 'file';
    public const TYPE_TASK_FILE = 'task_file';
    public const TYPE_MESSAGE_FILE = 'message_file';

    public const SOURCE_PROJECT = 'project';
    public const SOURCE_FILESYSTEM = 'filesystem';
    public const SOURCE_PROJECT_TASK = 'project_task';
    public const SOURCE_DIALOG = 'dialog';

    protected $fillable = [
        'userid',
        'target_type',
        'target_id',
        'source_type',
        'source_id',
        'browsed_at',
    ];

    protected $dates = [
        'browsed_at',
    ];

    public static function record(int $userid, string $targetType, int $targetId, string $sourceType = '', int $sourceId = 0): self
    {
        return self::updateOrCreate(
            [
                'userid' => $userid,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            [
                'browsed_at' => Carbon::now(),
            ]
        );
    }
}
