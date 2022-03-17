<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\ProjectFlowItem
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $flow_id 流程ID
 * @property string|null $name 名称
 * @property string|null $status 状态
 * @property array $turns 可流转
 * @property array $userids 状态负责人ID
 * @property string|null $usertype 流转模式
 * @property int|null $userlimit 限制负责人
 * @property int|null $sort 排序
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectFlow|null $projectFlow
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereFlowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereTurns($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereUserids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereUserlimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlowItem whereUsertype($value)
 * @mixin \Eloquent
 */
class ProjectFlowItem extends AbstractModel
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * @param $value
     * @return array
     */
    public function getTurnsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }

    /**
     * @param $value
     * @return array
     */
    public function getUseridsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function projectFlow(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectFlow::class, 'id', 'flow_id');
    }

    /**
     * @return bool|null
     */
    public function deleteFlowItem()
    {
        ProjectTask::whereFlowItemId($this->id)->update([
            'flow_item_id' => 0,
            'flow_item_name' => "",
        ]);
        return $this->delete();
    }
}
