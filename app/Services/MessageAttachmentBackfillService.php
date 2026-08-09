<?php

namespace App\Services;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgAttachmentBackfill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class MessageAttachmentBackfillService
{
    public const DEFAULT_BATCH_SIZE = 500;
    public const DEFAULT_TIME_LIMIT = 40;

    private const LOCK_KEY = 'collaboration-files:attachment-backfill';
    private const LOCK_SECONDS = 120;

    /**
     * 自动回填与手工回填共用同一套候选消息筛选规则。
     */
    public static function candidateQuery(int $after, int $before): Builder
    {
        return WebSocketDialogMsg::query()
            ->where('id', '>', $after)
            ->where('id', '<=', $before)
            ->where(function ($query) {
                $query->where('type', 'file')
                    ->orWhere(function ($text) {
                        $text->where('type', 'text')
                            ->where(function ($images) {
                                $images->where('msg', 'like', '%browse%')
                                    ->orWhere('msg', 'like', '%[:IMAGE:browse:%');
                            });
                    });
            })
            ->orderBy('id');
    }

    /**
     * 在时间预算内推进自动回填；已有任务运行时直接跳过。
     */
    public static function processAutomatic(
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        int $timeLimit = self::DEFAULT_TIME_LIMIT
    ): array {
        $batchSize = min(2000, max(1, $batchSize));
        $timeLimit = min(50, max(1, $timeLimit));
        $lockKey = config('app.env') . ':' . self::LOCK_KEY;
        $lock = Cache::store('redis')->lock($lockKey, self::LOCK_SECONDS);
        if (!$lock->get()) {
            return ['status' => 'locked'];
        }

        try {
            return self::processState($batchSize, $timeLimit);
        } finally {
            $lock->release();
        }
    }

    private static function processState(int $batchSize, int $timeLimit): array
    {
        $state = WebSocketDialogMsgAttachmentBackfill::query()->orderBy('id')->first();
        if (!$state) {
            return ['status' => 'missing'];
        }
        if ($state->status === WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED) {
            return ['status' => WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED];
        }
        if ($state->next_retry_at && $state->next_retry_at->isFuture()) {
            return ['status' => 'waiting'];
        }

        $before = intval($state->before_msg_id);
        if (!$state->started_at) {
            // 迁移到新服务启动之间仍可能由旧进程写入消息，首次运行时补齐这段升级窗口。
            $before = max($before, intval(WebSocketDialogMsg::withTrashed()->max('id')));
        }

        $state->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_RUNNING,
            'before_msg_id' => $before,
            'started_at' => $state->started_at ?: now(),
            'next_retry_at' => null,
        ]);

        $startedAt = microtime(true);
        $lastId = intval($state->last_msg_id);
        $processed = intval($state->processed_count);
        $attachmentCount = intval($state->attachment_count);
        $emptyCount = intval($state->empty_count);

        while ($lastId < $before && microtime(true) - $startedAt < $timeLimit) {
            $messages = self::candidateQuery($lastId, $before)
                ->take($batchSize)
                ->get();

            if ($messages->isEmpty()) {
                return self::complete($state, $before, $processed, $attachmentCount, $emptyCount);
            }

            foreach ($messages as $message) {
                try {
                    $count = MessageAttachmentService::sync($message);
                } catch (\Throwable $e) {
                    self::fail($state, $lastId, $processed, $attachmentCount, $emptyCount, $e);
                    return [
                        'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_FAILED,
                        'last_msg_id' => $lastId,
                    ];
                }

                $lastId = intval($message->id);
                $processed++;
                $attachmentCount += $count;
                if ($count === 0) {
                    $emptyCount++;
                }
            }

            $state->update([
                'last_msg_id' => $lastId,
                'processed_count' => $processed,
                'attachment_count' => $attachmentCount,
                'empty_count' => $emptyCount,
                'retry_count' => 0,
                'last_error' => null,
            ]);
        }

        if ($lastId >= $before) {
            return self::complete($state, $before, $processed, $attachmentCount, $emptyCount);
        }

        $state->update(['status' => WebSocketDialogMsgAttachmentBackfill::STATUS_PENDING]);
        return [
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_PENDING,
            'last_msg_id' => $lastId,
            'processed_count' => $processed,
            'attachment_count' => $attachmentCount,
        ];
    }

    private static function complete(
        WebSocketDialogMsgAttachmentBackfill $state,
        int $before,
        int $processed,
        int $attachmentCount,
        int $emptyCount
    ): array {
        $state->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED,
            'last_msg_id' => $before,
            'processed_count' => $processed,
            'attachment_count' => $attachmentCount,
            'empty_count' => $emptyCount,
            'retry_count' => 0,
            'last_error' => null,
            'next_retry_at' => null,
            'completed_at' => now(),
        ]);

        return [
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED,
            'last_msg_id' => $before,
            'processed_count' => $processed,
            'attachment_count' => $attachmentCount,
        ];
    }

    private static function fail(
        WebSocketDialogMsgAttachmentBackfill $state,
        int $lastId,
        int $processed,
        int $attachmentCount,
        int $emptyCount,
        \Throwable $error
    ): void {
        $retryCount = intval($state->retry_count) + 1;
        $retryMinutes = min(30, 2 ** min(4, $retryCount - 1));
        $state->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_FAILED,
            'last_msg_id' => $lastId,
            'processed_count' => $processed,
            'attachment_count' => $attachmentCount,
            'empty_count' => $emptyCount,
            'failure_count' => intval($state->failure_count) + 1,
            'retry_count' => $retryCount,
            'last_error' => mb_substr($error->getMessage(), 0, 2000),
            'next_retry_at' => now()->addMinutes($retryMinutes),
        ]);
    }
}
