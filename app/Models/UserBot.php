<?php

namespace App\Models;

/**
 * App\Models\UserBot
 *
 * @property int $id
 * @property int|null $userid 所属人ID
 * @property int|null $bot_id 机器人ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereBotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserBot whereUserid($value)
 * @mixin \Eloquent
 */
class UserBot extends AbstractModel
{

}
