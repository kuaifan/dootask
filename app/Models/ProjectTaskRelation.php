<?php

namespace App\Models;

use App\Module\Base;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProjectTaskRelation
 *
 * @property int $id
 * @property int $task_id 任务ID
 * @property int $related_task_id 关联任务ID
 * @property string $direction 关系方向: mention/mentioned_by
 * @property int|null $dialog_id 来源会话ID
 * @property int|null $msg_id 来源消息ID
 * @property int|null $userid 提及人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectTask|null $relatedTask
 * @property-read \App\Models\ProjectTask|null $task
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereRelatedTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskRelation whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskRelation extends AbstractModel
{
    public const DIRECTION_MENTION = 'mention';
    public const DIRECTION_MENTIONED_BY = 'mentioned_by';

    protected $fillable = [
        'task_id',
        'related_task_id',
        'direction',
        'dialog_id',
        'msg_id',
        'userid',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'related_task_id');
    }

    /**
     * 创建双向任务关联
     *
     * @param int $sourceTaskId 源任务ID
     * @param int $targetTaskId 目标任务ID
     * @param int|null $dialogId 来源对话ID
     * @param int|null $msgId 来源消息ID
     * @param int|null $userid 操作人
     * @param bool $push 是否推送更新
     * @return bool 是否创建成功
     */
    public static function createRelation(
        int $sourceTaskId,
        int $targetTaskId,
        ?int $dialogId = null,
        ?int $msgId = null,
        ?int $userid = null,
        bool $push = true
    ): bool {
        if ($sourceTaskId === $targetTaskId) {
            return false;
        }

        $sourceTask = ProjectTask::with('project')->find($sourceTaskId);
        $targetTask = ProjectTask::with('project')->find($targetTaskId);

        if (!$sourceTask || !$targetTask) {
            return false;
        }

        if ($sourceTask->deleted_at || $targetTask->deleted_at) {
            return false;
        }

        // 创建正向关联：源任务提及目标任务
        $mentionRelation = static::updateOrCreate(
            [
                'task_id' => $sourceTaskId,
                'related_task_id' => $targetTaskId,
                'direction' => self::DIRECTION_MENTION,
            ],
            [
                'dialog_id' => $dialogId,
                'msg_id' => $msgId,
                'userid' => $userid,
            ]
        );

        // 创建反向关联：目标任务被源任务提及
        $reverseRelation = static::updateOrCreate(
            [
                'task_id' => $targetTaskId,
                'related_task_id' => $sourceTaskId,
                'direction' => self::DIRECTION_MENTIONED_BY,
            ],
            [
                'dialog_id' => $dialogId,
                'msg_id' => $msgId,
                'userid' => $userid,
            ]
        );

        // 推送关联更新
        if ($push) {
            $needPush = $mentionRelation->wasRecentlyCreated || $mentionRelation->wasChanged()
                || $reverseRelation->wasRecentlyCreated || $reverseRelation->wasChanged();

            if ($needPush) {
                if ($sourceTask->project) {
                    $sourceTask->pushMsg('relation', null, null, false);
                }
                if ($targetTask->project) {
                    $targetTask->pushMsg('relation', null, null, false);
                }
            }
        }

        return true;
    }

    /**
     * 删除双向任务关联
     *
     * @param int $taskId 任务ID
     * @param int $relatedTaskId 关联任务ID
     * @return bool 是否删除成功
     */
    public static function deleteRelation(int $taskId, int $relatedTaskId): bool
    {
        // 删除正向关联
        $deleted1 = static::whereTaskId($taskId)
            ->whereRelatedTaskId($relatedTaskId)
            ->delete();

        // 删除反向关联
        $deleted2 = static::whereTaskId($relatedTaskId)
            ->whereRelatedTaskId($taskId)
            ->delete();

        if ($deleted1 || $deleted2) {
            // 推送关联更新
            $sourceTask = ProjectTask::with('project')->find($taskId);
            $targetTask = ProjectTask::with('project')->find($relatedTaskId);
            if ($sourceTask?->project) {
                $sourceTask->pushMsg('relation', null, null, false);
            }
            if ($targetTask?->project) {
                $targetTask->pushMsg('relation', null, null, false);
            }
            return true;
        }

        return false;
    }

    public static function recordMentionsFromMessage(WebSocketDialogMsg $msg): void
    {
        if ($msg->type !== 'text') {
            return;
        }

        $payload = $msg->msg;
        if (!is_array($payload)) {
            $payload = Base::json2array($msg->getRawOriginal('msg'));
        }

        $text = $payload['text'] ?? '';
        if (!$text || !preg_match_all('/<span class="mention task" data-id="(\d+)">#?(.*?)<\/span>/i', $text, $matches)) {
            return;
        }

        $targetIds = array_values(array_unique(array_filter(array_map('intval', $matches[1] ?? []))));
        if (empty($targetIds)) {
            return;
        }

        $sourceTaskIds = ProjectTask::whereDialogId($msg->dialog_id)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (empty($sourceTaskIds)) {
            return;
        }

        foreach ($sourceTaskIds as $sourceTaskId) {
            foreach ($targetIds as $targetId) {
                self::createRelation(
                    $sourceTaskId,
                    $targetId,
                    $msg->dialog_id,
                    $msg->id,
                    $msg->userid
                );
            }
        }
    }
}
