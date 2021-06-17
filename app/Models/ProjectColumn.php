<?php

namespace App\Models;

use App\Module\Base;
use App\Tasks\PushTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProjectColumn
 *
 * @package App\Models
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property string|null $name 列表名称
 * @property string|null $color 颜色
 * @property int|null $sort 排序(ASC)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectTask[] $projectTask
 * @property-read int|null $project_task_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn newQuery()
 * @method static \Illuminate\Database\Query\Builder|ProjectColumn onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|ProjectColumn withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ProjectColumn withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectColumn extends AbstractModel
{
    use SoftDeletes;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectTask(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectTask::class, 'column_id', 'id')->whereNull('archived_at')->orderBy('sort')->orderBy('id');
    }

    /**
     * 删除列表
     * @param bool $pushMsg 是否推送
     * @return bool
     */
    public function deleteColumn($pushMsg = true)
    {
        $result = AbstractModel::transaction(function () use ($pushMsg) {
            $tasks = ProjectTask::whereColumnId($this->id)->get();
            foreach ($tasks as $task) {
                $task->deleteTask($pushMsg);
            }
            if ($pushMsg) {
                $this->pushMsg("delete", $this->toArray());
            }
            $this->delete();
            $this->addLog("删除列表：" . $this->name);
            return Base::retSuccess('删除成功', $this->toArray());
        });
        return Base::isSuccess($result);
    }

    /**
     * 添加项目日志
     * @param string $detail
     * @param int $userid
     * @return ProjectLog
     */
    public function addLog($detail, $userid = 0)
    {
        $log = ProjectLog::createInstance([
            'project_id' => $this->project_id,
            'column_id' => $this->id,
            'task_id' => 0,
            'userid' => $userid ?: User::token2userid(),
            'detail' => $detail,
        ]);
        $log->save();
        return $log;
    }

    /**
     * 推送消息
     * @param string $action
     * @param array $data
     */
    public function pushMsg($action, $data)
    {
        if (!$this->project) {
            return;
        }
        $lists = [
            'userid' => $this->project->relationUserids(),
            'msg' => [
                'type' => 'projectColumn',
                'action' => $action,
                'data' => $data,
            ]
        ];
        $task = new PushTask($lists, false);
        Task::deliver($task);
    }
}
