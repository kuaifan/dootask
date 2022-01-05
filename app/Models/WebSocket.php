<?php

namespace App\Models;


/**
 * App\Models\WebSocket
 *
 * @property int $id
 * @property string $key
 * @property string|null $fd
 * @property string|null $path
 * @property int|null $userid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereFd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocket extends AbstractModel
{
}
