<?php

namespace Tests\Feature;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgAttachment;
use App\Models\WebSocketDialogMsgAttachmentBackfill;
use App\Services\MessageAttachmentBackfillService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MessageAttachmentBackfillServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_automatic_backfill_advances_persisted_cursor_to_completion(): void
    {
        $fileMessage = $this->makeMessage('file', [
            'name' => 'backfill.pdf',
            'ext' => 'pdf',
            'path' => 'uploads/test/backfill.pdf',
            'size' => 1024,
        ]);
        $imageMessage = $this->makeMessage('text', [
            'text' => '<p><img class="browse" width="320" height="180" src="uploads/test/backfill.png_thumb.jpg"/></p>',
        ]);

        $state = WebSocketDialogMsgAttachmentBackfill::query()->firstOrFail();
        $state->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_PENDING,
            // 模拟迁移后、服务重启前又产生了一条图片消息。
            'before_msg_id' => $fileMessage->id,
            'last_msg_id' => $fileMessage->id - 1,
            'processed_count' => 0,
            'attachment_count' => 0,
            'empty_count' => 0,
            'failure_count' => 0,
            'retry_count' => 0,
            'last_error' => null,
            'started_at' => null,
            'completed_at' => null,
            'next_retry_at' => null,
        ]);

        $result = MessageAttachmentBackfillService::processAutomatic(1, 5);

        $state->refresh();
        $this->assertSame(WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED, $result['status']);
        $this->assertSame(WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED, $state->status);
        $this->assertSame($imageMessage->id, $state->before_msg_id);
        $this->assertSame($imageMessage->id, $state->last_msg_id);
        $this->assertSame(2, $state->processed_count);
        $this->assertSame(2, $state->attachment_count);
        $this->assertNotNull($state->completed_at);
        $this->assertSame(2, WebSocketDialogMsgAttachment::whereIn('msg_id', [
            $fileMessage->id,
            $imageMessage->id,
        ])->count());
    }

    public function test_automatic_backfill_waits_until_retry_time(): void
    {
        $state = WebSocketDialogMsgAttachmentBackfill::query()->firstOrFail();
        $state->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_FAILED,
            'next_retry_at' => now()->addMinutes(5),
        ]);

        $result = MessageAttachmentBackfillService::processAutomatic(1, 1);

        $this->assertSame('waiting', $result['status']);
        $this->assertSame(WebSocketDialogMsgAttachmentBackfill::STATUS_FAILED, $state->fresh()->status);
    }

    private function makeMessage(string $type, array $data): WebSocketDialogMsg
    {
        $message = WebSocketDialogMsg::createInstance([
            'dialog_id' => 0,
            'userid' => 0,
            'type' => $type,
            'key' => '',
            'msg' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
        $message->save();
        return $message;
    }
}
