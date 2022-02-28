<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskFlowChange
 *
 * @property int $id
 * @property int|null $task_id 任务ID
 * @property int|null $userid 会员ID
 * @property int|null $before_flow_item_id （变化前）工作流状态ID
 * @property string|null $before_flow_item_name （变化前）工作流状态名称
 * @property int|null $after_flow_item_id （变化后）工作流状态ID
 * @property string|null $after_flow_item_name （变化后）工作流状态名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereAfterFlowItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereAfterFlowItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereBeforeFlowItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereBeforeFlowItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskFlowChange whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskFlowChange extends AbstractModel
{

}
