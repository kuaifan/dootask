<?php

namespace App\Models;

/**
 * AI 助手回复反馈（👍/👎）
 *
 * @property int $id
 * @property int $userid
 * @property string $session_key
 * @property string $session_id
 * @property int $local_id
 * @property string $feedback
 * @property string|null $prompt
 * @property string $answer_digest
 * @property string|null $answer
 * @property string|null $source_ids
 * @property string $model
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiAssistantFeedback extends AbstractModel
{
    protected $table = 'ai_assistant_feedbacks';
}
