<?php

namespace App\Models;

use App\Module\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProjectColumn
 *
 * @package App\Models
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property string|null $name 列表名称
 * @property int|null $inorder 排序(ASC)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectTask[] $projectTask
 * @property-read int|null $project_task_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereInorder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectColumn whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectColumn extends AbstractModel
{
    use SoftDeletes;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectTask(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectTask::class, 'column_id', 'id')->orderByDesc('id');
    }

    /**
     * 删除列表
     * @return bool
     */
    public function deleteColumn()
    {
        $result = AbstractModel::transaction(function () {
            ProjectTask::whereColumnId($this->id)->delete();
            if ($this->delete()) {
                return Base::retSuccess('success');
            } else {
                return Base::retError('error');
            }
        });
        return Base::isSuccess($result);
    }
}
