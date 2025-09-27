<?php

namespace App\Models;

use App\Module\Base;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        $sourceTasks = ProjectTask::with('project')->whereDialogId($msg->dialog_id)->get();
        if ($sourceTasks->isEmpty()) {
            return;
        }

        $targetTasks = ProjectTask::with('project')->whereIn('id', $targetIds)->get()->keyBy('id');
        if ($targetTasks->isEmpty()) {
            return;
        }

        $pushTasks = [];
        foreach ($sourceTasks as $sourceTask) {
            foreach ($targetIds as $targetId) {
                if ($targetId === $sourceTask->id) {
                    continue;
                }

                $targetTask = $targetTasks->get($targetId);
                if (!$targetTask) {
                    continue;
                }

                $mentionRelation = static::updateOrCreate(
                    [
                        'task_id' => $sourceTask->id,
                        'related_task_id' => $targetTask->id,
                        'direction' => self::DIRECTION_MENTION,
                    ],
                    [
                        'dialog_id' => $msg->dialog_id,
                        'msg_id' => $msg->id,
                        'userid' => $msg->userid,
                    ]
                );

                if ($mentionRelation->wasRecentlyCreated || $mentionRelation->wasChanged()) {
                    $pushTasks[$sourceTask->id] = $sourceTask;
                }

                $reverseRelation = static::updateOrCreate(
                    [
                        'task_id' => $targetTask->id,
                        'related_task_id' => $sourceTask->id,
                        'direction' => self::DIRECTION_MENTIONED_BY,
                    ],
                    [
                        'dialog_id' => $msg->dialog_id,
                        'msg_id' => $msg->id,
                        'userid' => $msg->userid,
                    ]
                );

                if ($reverseRelation->wasRecentlyCreated || $reverseRelation->wasChanged()) {
                    $pushTasks[$targetTask->id] = $targetTask;
                }
            }
        }

        foreach ($pushTasks as $task) {
            $task->loadMissing('project');
            if (!$task->project) {
                continue;
            }

            $task->pushMsg('relation', null, null, false);
        }
    }
}
