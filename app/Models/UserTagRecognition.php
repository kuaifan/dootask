<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
