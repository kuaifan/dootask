<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskUser
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property int|null $task_pid 任务ID（如果是子任务则是父级任务ID）
 * @property int|null $userid 成员ID
 * @property int|null $owner 是否任务负责人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereTaskPid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskUser extends AbstractModel
{

}
