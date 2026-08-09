<?php

namespace App\Services;

use App\Models\File;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgAttachment;
use App\Module\Base;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageAttachmentService
{
    /**
     * 从消息内容提取可检索附件。返回值不包含消息关联字段和时间字段。
     */
    public static function extract(string $messageType, array $messageData): array
    {
        if ($messageType === 'file') {
            $attachment = self::extractFileMessage($messageData);
            return $attachment ? [$attachment] : [];
        }

        if ($messageType !== 'text') {
            return [];
        }

        return self::extractInlineImages((string)($messageData['text'] ?? ''));
    }

    /**
     * 将一条消息的附件索引同步为当前内容，重复执行不会产生重复记录。
     */
    public static function sync(WebSocketDialogMsg $message, bool $removeMissing = true): int
    {
        $messageData = Base::json2array($message->getRawOriginal('msg'));
        $attachments = self::extract((string)$message->type, $messageData);
        if (empty($attachments) && !$removeMissing) {
            return 0;
        }

        DB::transaction(function () use ($message, $attachments) {
            if (empty($attachments)) {
                WebSocketDialogMsgAttachment::whereMsgId($message->id)->delete();
                return;
            }

            $now = now();
            $sourceType = $attachments[0]['source_type'];
            $positions = [];
            $rows = [];
            foreach ($attachments as $attachment) {
                $positions[] = $attachment['position'];
                $rows[] = array_merge($attachment, [
                    'msg_id' => intval($message->id),
                    'dialog_id' => intval($message->dialog_id),
                    'created_at' => $message->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('web_socket_dialog_msg_attachments')->upsert(
                $rows,
                ['msg_id', 'source_type', 'position'],
                ['dialog_id', 'kind', 'name', 'ext', 'path', 'thumb', 'size', 'width', 'height', 'updated_at']
            );

            WebSocketDialogMsgAttachment::whereMsgId($message->id)
                ->where(function ($query) use ($sourceType, $positions) {
                    $query->where('source_type', '!=', $sourceType)
                        ->orWhereNotIn('position', $positions);
                })
                ->delete();
        });

        return count($attachments);
    }

    /**
     * 双写失败不影响聊天主链路，历史回填命令可再次同步失败消息。
     */
    public static function syncSafely(WebSocketDialogMsg $message, bool $removeMissing = false): bool
    {
        if (!in_array($message->type, ['file', 'text'], true)) {
            return true;
        }

        try {
            self::sync($message, $removeMissing);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Message attachment sync failed', [
                'msg_id' => intval($message->id),
                'dialog_id' => intval($message->dialog_id),
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private static function extractFileMessage(array $messageData): ?array
    {
        $path = self::normalizeStoredPath((string)($messageData['path'] ?? ''));
        if ($path === '') {
            return null;
        }

        $ext = strtolower((string)($messageData['ext'] ?? self::pathExtension($path)));
        $thumb = self::normalizeStoredPath((string)($messageData['thumb'] ?? ''));

        return [
            'source_type' => WebSocketDialogMsgAttachment::SOURCE_FILE_MESSAGE,
            'kind' => in_array($ext, File::imageExt, true)
                ? WebSocketDialogMsgAttachment::KIND_IMAGE
                : WebSocketDialogMsgAttachment::KIND_FILE,
            'position' => 0,
            'name' => (string)($messageData['name'] ?? ''),
            'ext' => $ext,
            'path' => $path,
            'thumb' => $thumb,
            'size' => max(0, intval($messageData['size'] ?? 0)),
            'width' => max(0, intval($messageData['width'] ?? 0)),
            'height' => max(0, intval($messageData['height'] ?? 0)),
        ];
    }

    private static function extractInlineImages(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $attachments = [];
        if (str_contains($html, '<img')) {
            $document = new DOMDocument('1.0', 'UTF-8');
            $previous = libxml_use_internal_errors(true);
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8"><div>' . $html . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
            );
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if ($loaded) {
                $nodes = (new DOMXPath($document))->query(
                    "//img[contains(concat(' ', normalize-space(@class), ' '), ' browse ')]"
                );
                foreach ($nodes ?: [] as $node) {
                    if (!$node instanceof DOMElement) {
                        continue;
                    }
                    self::appendInlineImage($attachments, [
                        'src' => $node->getAttribute('src'),
                        'width' => $node->getAttribute('width'),
                        'height' => $node->getAttribute('height'),
                        'name' => $node->getAttribute('alt'),
                    ]);
                }
            }
        }

        if (empty($attachments)) {
            preg_match_all('/\[:IMAGE:browse:([^:]*):([^:]*):(.*?):(.*?):\]/i', $html, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                self::appendInlineImage($attachments, [
                    'src' => $match[3] ?? '',
                    'width' => $match[1] ?? 0,
                    'height' => $match[2] ?? 0,
                    'name' => $match[4] ?? '',
                ]);
            }
        }

        foreach ($attachments as $position => &$attachment) {
            $attachment['position'] = $position;
        }
        unset($attachment);

        return $attachments;
    }

    private static function appendInlineImage(array &$attachments, array $image): void
    {
        $thumb = self::normalizeStoredPath(html_entity_decode(trim((string)$image['src']), ENT_QUOTES | ENT_HTML5));
        if ($thumb === '' || str_contains($thumb, 'images/other/imgerr.jpg')) {
            return;
        }

        $path = Base::thumbRestore($thumb);
        $attachments[] = [
            'source_type' => WebSocketDialogMsgAttachment::SOURCE_INLINE_IMAGE,
            'kind' => WebSocketDialogMsgAttachment::KIND_IMAGE,
            'position' => 0,
            'name' => trim(strip_tags((string)$image['name'])),
            'ext' => self::pathExtension($path),
            'path' => $path,
            'thumb' => $thumb,
            'size' => self::localFileSize($path),
            'width' => max(0, intval($image['width'])),
            'height' => max(0, intval($image['height'])),
        ];
    }

    private static function normalizeStoredPath(string $path): string
    {
        $path = trim(str_replace('{{RemoteURL}}', '', $path));
        if ($path === '' || str_starts_with($path, 'data:')) {
            return '';
        }

        $path = Base::unFillUrl($path);
        if (str_starts_with($path, '/') && !str_starts_with($path, '//')) {
            $path = ltrim($path, '/');
        }
        return $path;
    }

    private static function pathExtension(string $path): string
    {
        $urlPath = parse_url($path, PHP_URL_PATH);
        return strtolower(pathinfo(urldecode((string)($urlPath ?: $path)), PATHINFO_EXTENSION));
    }

    private static function localFileSize(string $path): int
    {
        if ($path === '' || preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $path)) {
            return 0;
        }

        $file = public_path(ltrim($path, '/'));
        return is_file($file) ? max(0, intval(filesize($file))) : 0;
    }
}
