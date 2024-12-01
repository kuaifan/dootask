<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskTemplate
 *
 * @property int $id
 * @property int $project_id 项目ID
 * @property string $name 模板名称
 * @property string|null $title 任务标题
 * @property string|null $content 任务内容
 * @property int $sort 排序（预留）
 * @property bool $is_default 是否默认模板
 * @property int $userid 创建人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectTaskTemplate extends AbstractModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'name',
        'title',
        'content',
        'sort',
        'is_default',
        'userid'
    ];

    /**
     * 关联项目
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 关联创建者
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
