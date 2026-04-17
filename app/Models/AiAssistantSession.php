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
 */
class AiAssistantSession extends AbstractModel
{
    protected $table = 'ai_assistant_sessions';
}
