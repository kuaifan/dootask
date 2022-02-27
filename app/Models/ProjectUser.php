<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\ProjectUser
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $userid 成员ID
 * @property int|null $owner 是否负责人
 * @property string|null $top_at 置顶时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereTopAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectUser extends AbstractModel
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    /**
     * 退出项目
     */
    public function exitProject()
    {
        ProjectTaskUser::whereProjectId($this->project_id)
            ->whereUserid($this->userid)
            ->chunk(100, function ($list) {
                $tastIds = [];
                foreach ($list as $item) {
                    if (!in_array($item->task_pid, $tastIds)) {
                        $tastIds[] = $item->task_pid;
                    }
                    $item->delete();
                }
                $tasks = ProjectTask::whereIn('id', $tastIds)->get();
                foreach ($tasks as $task) {
                    $task->syncDialogUser();
                }
            });
        $this->delete();
    }
}
