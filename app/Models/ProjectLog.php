<?php

namespace App\Models;

/**
 * App\Models\ProjectLog
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $column_id 列表ID
 * @property int|null $task_id 项目ID
 * @property int|null $userid 会员ID
 * @property string|null $detail 详细信息
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereColumnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLog whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectLog extends AbstractModel
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'userid', 'userid');
    }

}
