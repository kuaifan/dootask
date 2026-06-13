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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog change($array)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog remove()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereContextKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereDurationMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereEmpty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereQuery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereResultCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereSourceIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereTopScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSearchLog whereUserid($value)
 * @mixin \Eloquent
 */
class AiAssistantSearchLog extends AbstractModel
{
    protected $table = 'ai_assistant_search_logs';
}
