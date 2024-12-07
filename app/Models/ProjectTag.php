<?php

namespace App\Models;

/**
 * App\Models\ProjectTag
 *
 * @property int $id
 * @property int $project_id 项目ID
 * @property string $name 标签名称
 * @property string|null $desc 标签描述
 * @property string|null $color 颜色
 * @property int $userid 创建人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTag whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTag extends AbstractModel
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'name',
        'desc',
        'color',
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
}
