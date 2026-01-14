<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\UserTagRecognition
 *
 * @property int $id
 * @property int $tag_id 标签ID
 * @property int $user_id 认可人ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserTag $tag
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTagRecognition whereUserId($value)
 * @mixin \Eloquent
 */
class UserTagRecognition extends AbstractModel
{
    protected $table = 'user_tag_recognitions';

    protected $fillable = [
        'tag_id',
        'user_id',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(UserTag::class, 'tag_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'userid')
            ->select(['userid', 'nickname']);
    }
}
