<?php

namespace App\Models;

/**
 * @property int $id
 * @property string $status
 * @property int $before_msg_id
 * @property int $last_msg_id
 * @property int $processed_count
 * @property int $attachment_count
 * @property int $empty_count
 * @property int $failure_count
 * @property int $retry_count
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class WebSocketDialogMsgAttachmentBackfill extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';

    protected $table = 'web_socket_dialog_msg_attachment_backfills';

    protected $fillable = [
        'status',
        'before_msg_id',
        'last_msg_id',
        'processed_count',
        'attachment_count',
        'empty_count',
        'failure_count',
        'retry_count',
        'last_error',
        'started_at',
        'completed_at',
        'next_retry_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];
}
