<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskVisibilityUser
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property int|null $userid 成员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectTask|null $projectTask
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskVisibilityUser whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskVisibilityUser extends AbstractModel
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function projectTask(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectTask::class, 'id', 'task_id');
    }

}
