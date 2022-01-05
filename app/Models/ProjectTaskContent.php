<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskContent
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property string|null $content 内容
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectTaskContent extends AbstractModel
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
