<?php

namespace Tests\Unit;

use App\Models\WebSocketDialogMsgAttachment;
use App\Services\MessageAttachmentService;
use Tests\TestCase;

class MessageAttachmentServiceTest extends TestCase
{
    public function test_extracts_file_message_metadata(): void
    {
        $attachments = MessageAttachmentService::extract('file', [
            'name' => '方案.pdf',
            'ext' => 'PDF',
            'path' => 'uploads/chat/202608/1/plan.pdf',
            'thumb' => 'images/ext/pdf.png',
            'size' => 2048,
        ]);

        $this->assertCount(1, $attachments);
        $this->assertSame(WebSocketDialogMsgAttachment::SOURCE_FILE_MESSAGE, $attachments[0]['source_type']);
        $this->assertSame(WebSocketDialogMsgAttachment::KIND_FILE, $attachments[0]['kind']);
        $this->assertSame('pdf', $attachments[0]['ext']);
        $this->assertSame(2048, $attachments[0]['size']);
    }

    public function test_classifies_uploaded_image_as_image_attachment(): void
    {
        $attachments = MessageAttachmentService::extract('file', [
            'name' => 'design.png',
            'ext' => 'png',
            'path' => 'uploads/chat/202608/1/design.png',
            'thumb' => 'uploads/chat/202608/1/design.png_thumb.jpg',
            'size' => 4096,
            'width' => 1200,
            'height' => 800,
        ]);

        $this->assertSame(WebSocketDialogMsgAttachment::KIND_IMAGE, $attachments[0]['kind']);
        $this->assertSame(1200, $attachments[0]['width']);
        $this->assertSame(800, $attachments[0]['height']);
    }

    public function test_extracts_each_browse_image_and_ignores_emoticons(): void
    {
        $html = <<<'HTML'
<p>
    <img class="browse" width="640" height="480" src="{{RemoteURL}}uploads/chat/202608/1/design.png_thumb.jpg" alt="设计图"/>
    <img class="emoticon" width="32" height="32" src="images/emoji/smile.png" alt="笑脸"/>
    <img class="browse extra" width="320" height="240" src="{{RemoteURL}}uploads/chat/202608/1/design.png_thumb.jpg" alt="重复图片"/>
</p>
HTML;

        $attachments = MessageAttachmentService::extract('text', ['text' => $html]);

        $this->assertCount(2, $attachments);
        $this->assertSame([0, 1], array_column($attachments, 'position'));
        $this->assertSame('uploads/chat/202608/1/design.png', $attachments[0]['path']);
        $this->assertSame('uploads/chat/202608/1/design.png_thumb.jpg', $attachments[0]['thumb']);
        $this->assertSame('设计图', $attachments[0]['name']);
        $this->assertSame('重复图片', $attachments[1]['name']);
    }

    public function test_extracts_legacy_image_token(): void
    {
        $attachments = MessageAttachmentService::extract('text', [
            'text' => '<p>旧消息</p>[:IMAGE:browse:120:80:uploads/chat/202001/1/legacy.webp:旧截图:]',
        ]);

        $this->assertCount(1, $attachments);
        $this->assertSame('uploads/chat/202001/1/legacy.webp', $attachments[0]['path']);
        $this->assertSame('webp', $attachments[0]['ext']);
        $this->assertSame(120, $attachments[0]['width']);
        $this->assertSame(80, $attachments[0]['height']);
    }

    public function test_ignores_broken_placeholder_and_non_attachment_messages(): void
    {
        $this->assertSame([], MessageAttachmentService::extract('text', [
            'text' => '<img class="browse" width="90" height="90" src="images/other/imgerr.jpg"/>',
        ]));
        $this->assertSame([], MessageAttachmentService::extract('meeting', ['name' => '周会']));
    }
}
