<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProjectTaskAiEvent
 *
 * @property int $id
 * @property int $task_id 任务ID
 * @property string $event_type 事件类型
 * @property string $status 状态
 * @property int $retry_count 重试次数
 * @property array|null $result 执行结果
 * @property string|null $error 错误信息
 * @property int $msg_id 消息ID
 * @property \Illuminate\Support\Carbon|null $executed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectTask|null $task
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent change($array)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent remove()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereExecutedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereRetryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskAiEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectTaskAiEvent extends AbstractModel
{
    const EVENT_DESCRIPTION = 'description';
    const EVENT_SUBTASKS = 'subtasks';
    const EVENT_ASSIGNEE = 'assignee';
    const EVENT_SIMILAR = 'similar';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_APPLIED = 'applied';
    const STATUS_DISMISSED = 'dismissed';

    const MAX_RETRY = 3;

    protected $table = 'project_task_ai_events';

    protected $fillable = [
        'task_id',
        'event_type',
        'status',
        'retry_count',
        'result',
        'error',
        'msg_id',
        'executed_at',
    ];

    protected $casts = [
        'result' => 'array',
        'executed_at' => 'datetime',
    ];

    /**
     * 关联任务
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id', 'id');
    }

    /**
     * 获取所有事件类型
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_DESCRIPTION,
            self::EVENT_SUBTASKS,
            self::EVENT_ASSIGNEE,
            self::EVENT_SIMILAR,
        ];
    }

    /**
     * 标记为处理中
     */
    public function markProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    /**
     * 标记为完成
     */
    public function markCompleted(array $result, int $msgId = 0): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'result' => $result,
            'msg_id' => $msgId,
            'executed_at' => now(),
        ]);
    }

    /**
     * 标记为失败
     */
    public function markFailed(string $error): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'retry_count' => $this->retry_count + 1,
            'error' => $error,
            'executed_at' => now(),
        ]);
    }

    /**
     * 标记为跳过
     */
    public function markSkipped(string $reason = ''): bool
    {
        return $this->update([
            'status' => self::STATUS_SKIPPED,
            'error' => $reason,
            'executed_at' => now(),
        ]);
    }

    /**
     * 是否可以重试
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->retry_count < self::MAX_RETRY;
    }

    /**
     * 标记为已采纳
     */
    public function markApplied(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPLIED,
        ]);
    }

    /**
     * 标记为已忽略
     */
    public function markDismissed(): bool
    {
        return $this->update([
            'status' => self::STATUS_DISMISSED,
        ]);
    }
}
