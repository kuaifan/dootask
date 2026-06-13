<?php

namespace App\Models;

/**
 * AI 助手会话
 *
 * @property int $id
 * @property int $userid
 * @property string $session_key
 * @property string $session_id
 * @property string $scene_key
 * @property string $title
 * @property string|null $data
 * @property string|null $images
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession change($array)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession remove()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereSceneKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereSessionKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiAssistantSession whereUserid($value)
 * @mixin \Eloquent
 */
class AiAssistantSession extends AbstractModel
{
    protected $table = 'ai_assistant_sessions';
}
