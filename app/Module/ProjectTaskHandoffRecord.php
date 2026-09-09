<?php

namespace App\Module;

use App\Models\AbstractModel;
use App\Models\ProjectTask;
use App\Models\ProjectTaskHandoff;
use App\Models\ProjectTaskUser;
use App\Services\RequestContext;
use Closure;

class ProjectTaskHandoffRecord
{
    public const FIELDS = [
        'flow_item_id',
        'flow_item_name',
        'complete_at',
        'archived_at',
    ];

    public static function owners(int $taskId): array
    {
        return ProjectTaskUser::whereTaskId($taskId)
            ->whereOwner(1)
            ->orderBy('userid')
            ->pluck('userid')
            ->map(fn ($id) => (int)$id)
            ->all();
    }

    public static function snapshot(ProjectTask $task): array
    {
        $state = [];
        foreach (self::FIELDS as $field) {
            $state[$field] = $task->getRawOriginal($field);
        }
        $state['owners'] = self::owners($task->id);
        return $state;
    }

    public static function active(int $taskId): bool
    {
        return (bool)RequestContext::get('project_task_handoff_' . $taskId, false);
    }

    /**
     * 同一请求、同一任务的嵌套状态与负责人变更合并为一条流转记录。
     */
    public static function track(ProjectTask $task, string $source, Closure $callback, string $note = '')
    {
        if (self::active($task->id)) {
            return $callback();
        }
        return AbstractModel::transaction(function () use ($task, $source, $callback, $note) {
            $locked = ProjectTask::withTrashed()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $before = self::snapshot($locked);
            $task->setRawAttributes($locked->getAttributes(), true);
            $task->unsetRelations();
            $key = 'project_task_handoff_' . $task->id;
            RequestContext::save($key, true);
            try {
                $result = $callback();
                self::write($task->id, $source, $before, self::snapshot($task->fresh()), $note);
                return $result;
            } finally {
                RequestContext::save($key, false);
            }
        });
    }

    public static function created(ProjectTask $task, string $source = 'create'): void
    {
        self::write($task->id, $source, null, self::snapshot($task->fresh()));
    }

    public static function updated(ProjectTask $task): void
    {
        if (self::active($task->id) || !$task->isDirty(self::FIELDS)) {
            return;
        }
        $before = self::snapshot($task);
        $after = $before;
        foreach (self::FIELDS as $field) {
            $after[$field] = $task->getAttributes()[$field] ?? null;
        }
        self::write($task->id, 'update', $before, $after);
    }

    public static function write(int $taskId, string $source, ?array $before, array $after, string $note = ''): void
    {
        if ($before === $after) {
            return;
        }
        $event = ProjectTaskHandoff::createInstance([
            'task_id' => $taskId,
            'userid' => Doo::userId(),
            'source' => $source,
        ]);
        $event->record = [
            'before' => $before,
            'after' => $after,
            'note' => $note,
        ];
        $event->save();
    }
}
