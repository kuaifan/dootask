<?php

namespace App\Console\Commands;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgAttachmentBackfill;
use App\Module\Base;
use App\Services\MessageAttachmentBackfillService;
use App\Services\MessageAttachmentService;
use Illuminate\Console\Command;

class BackfillMessageAttachments extends Command
{
    protected $signature = 'collaboration-files:backfill-attachments
        {--after=0 : 从此消息ID之后开始}
        {--before=0 : 处理到此消息ID，默认取启动时的最大ID}
        {--batch=500 : 每批处理消息数量}
        {--limit=0 : 本次最多处理消息数量，0表示不限}
        {--sleep=0 : 每批结束后的休眠毫秒数}
        {--status : 查看自动回填状态，不处理消息}
        {--dry-run : 只解析和统计，不写入附件表}';

    protected $description = '分批回填协作文件所需的消息附件索引';

    public function handle(): int
    {
        if ($this->option('status')) {
            return $this->showStatus();
        }

        $after = max(0, intval($this->option('after')));
        $before = max(0, intval($this->option('before')));
        $batch = min(2000, max(1, intval($this->option('batch'))));
        $limit = max(0, intval($this->option('limit')));
        $sleep = min(60000, max(0, intval($this->option('sleep'))));
        $dryRun = boolval($this->option('dry-run'));

        if ($before === 0) {
            $before = intval(WebSocketDialogMsg::withTrashed()->max('id'));
        }
        if ($before <= $after) {
            $this->info("没有需要处理的消息（after={$after}, before={$before}）");
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '开始回填消息附件：范围 (%d, %d]，批量 %d%s',
            $after,
            $before,
            $batch,
            $dryRun ? '，仅预览' : ''
        ));

        $lastId = $after;
        $processed = 0;
        $attachmentCount = 0;
        $emptyCount = 0;
        $failedIds = [];

        while ($lastId < $before && ($limit === 0 || $processed < $limit)) {
            $take = $limit > 0 ? min($batch, $limit - $processed) : $batch;
            $messages = MessageAttachmentBackfillService::candidateQuery($lastId, $before)
                ->take($take)
                ->get();

            if ($messages->isEmpty()) {
                break;
            }

            foreach ($messages as $message) {
                $lastId = intval($message->id);
                $processed++;
                try {
                    if ($dryRun) {
                        $data = Base::json2array($message->getRawOriginal('msg'));
                        $count = count(MessageAttachmentService::extract((string)$message->type, $data));
                    } else {
                        $count = MessageAttachmentService::sync($message);
                    }
                    $attachmentCount += $count;
                    if ($count === 0) {
                        $emptyCount++;
                    }
                } catch (\Throwable $e) {
                    $failedIds[] = $lastId;
                    $this->warn("消息 {$lastId} 处理失败：{$e->getMessage()}");
                }
            }

            $this->line("已处理 {$processed} 条消息，附件 {$attachmentCount} 条，当前消息ID {$lastId}");
            if ($sleep > 0 && $lastId < $before) {
                usleep($sleep * 1000);
            }
        }

        $this->newLine();
        $this->info("回填结束：消息 {$processed} 条，附件 {$attachmentCount} 条，无有效附件 {$emptyCount} 条");
        $this->info("续跑参数：--after={$lastId} --before={$before}");
        if (!empty($failedIds)) {
            $this->error('失败消息ID：' . implode(',', $failedIds));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function showStatus(): int
    {
        $state = WebSocketDialogMsgAttachmentBackfill::query()->orderBy('id')->first();
        if (!$state) {
            $this->warn('自动回填任务尚未登记，请先执行数据库迁移');
            return self::FAILURE;
        }

        $this->table(['项目', '值'], [
            ['状态', $state->status],
            ['消息快照上界', $state->before_msg_id],
            ['当前消息ID', $state->last_msg_id],
            ['成功处理消息', $state->processed_count],
            ['已索引附件', $state->attachment_count],
            ['无有效附件消息', $state->empty_count],
            ['失败尝试', $state->failure_count],
            ['当前连续重试', $state->retry_count],
            ['下次重试时间', $state->next_retry_at?->toDateTimeString() ?: '-'],
            ['最近错误', $state->last_error ?: '-'],
            ['首次开始时间', $state->started_at?->toDateTimeString() ?: '-'],
            ['完成时间', $state->completed_at?->toDateTimeString() ?: '-'],
        ]);
        return self::SUCCESS;
    }
}
