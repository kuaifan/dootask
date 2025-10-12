<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserTag extends AbstractModel
{
    protected $table = 'user_tags';

    protected $fillable = [
        'user_id',
        'name',
        'created_by',
        'updated_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'userid')
            ->select(['userid', 'nickname']);
    }

    public function recognitions(): HasMany
    {
        return $this->hasMany(UserTagRecognition::class, 'tag_id');
    }

    public function canManage(User $viewer): bool
    {
        return $viewer->isAdmin()
            || $viewer->userid === $this->user_id
            || $viewer->userid === $this->created_by;
    }

    public static function listWithMeta(int $targetUserId, ?User $viewer): array
    {
        $query = static::query()
            ->where('user_id', $targetUserId)
            ->with(['creator'])
            ->withCount(['recognitions as recognition_total'])
            ->orderByDesc('recognition_total')
            ->orderBy('id');

        $tags = $query->get();

        $viewerId = $viewer?->userid ?? 0;
        $viewerIsAdmin = $viewer?->isAdmin() ?? false;
        $viewerIsOwner = $viewerId > 0 && $viewerId === $targetUserId;

        $recognizedIds = [];
        if ($viewerId > 0 && $tags->isNotEmpty()) {
            $recognizedIds = UserTagRecognition::query()
                ->where('user_id', $viewerId)
                ->whereIn('tag_id', $tags->pluck('id'))
                ->pluck('tag_id')
                ->all();
        }
        $recognizedLookup = array_flip($recognizedIds);

        $list = $tags->map(function (self $tag) use ($viewerId, $viewerIsAdmin, $viewerIsOwner, $recognizedLookup) {
            $canManage = $viewerIsAdmin || $viewerIsOwner || $viewerId === $tag->created_by;

            return [
                'id' => $tag->id,
                'user_id' => $tag->user_id,
                'name' => $tag->name,
                'created_by' => $tag->created_by,
                'created_by_name' => $tag->creator?->nickname ?: '',
                'recognition_total' => (int) $tag->recognition_total,
                'recognized' => isset($recognizedLookup[$tag->id]),
                'can_edit' => $canManage,
                'can_delete' => $canManage,
            ];
        })->values()->toArray();

        return [
            'list' => $list,
            'top' => array_slice($list, 0, 10),
            'total' => count($list),
        ];
    }
}
