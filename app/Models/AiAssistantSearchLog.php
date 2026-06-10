<?php

namespace App\Models;

/**
 * AI 助手帮助知识库检索日志
 *
 * @property int $id
 * @property int $userid
 * @property int $dialog_id
 * @property string $context_key
 * @property string $source
 * @property string $query
 * @property string $locale
 * @property string|null $source_ids
 * @property float $top_score
 * @property int $result_count
 * @property int $duration_ms
 * @property int $empty
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiAssistantSearchLog extends AbstractModel
{
    protected $table = 'ai_assistant_search_logs';
}
