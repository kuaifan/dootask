<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\UserTag
 *
 * @property int $id
 * @property int $user_id 被标签用户ID
 * @property string $name 标签名称
 * @property int $created_by 创建人
 * @property int|null $updated_by 最后更新人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserTagRecognition> $recognitions
 * @property-read int|null $recognitions_count
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTag whereUserId($value)
 * @mixin \Eloquent
 */
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
