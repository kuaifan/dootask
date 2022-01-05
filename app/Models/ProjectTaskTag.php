<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskTag
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property string|null $name 标题
 * @property string|null $color 颜色
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectTaskTag extends AbstractModel
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
