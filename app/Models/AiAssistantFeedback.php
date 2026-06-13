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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback change($array)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback remove()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereAnswerDigest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereLocalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback wherePrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereSessionKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereSourceIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantFeedback whereUserid($value)
 * @mixin \Eloquent
 */
class AiAssistantFeedback extends AbstractModel
{
    protected $table = 'ai_assistant_feedbacks';
}
