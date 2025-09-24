<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * @property int $id
 * @property int $userid
 * @property string $target_type
 * @property int $target_id
 * @property string $source_type
 * @property int $source_id
 * @property Carbon|null $browsed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
