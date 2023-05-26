<?php

namespace App\Models;

/**
 * App\Models\WorkflowProcMsg
 *
 * @property int $id
 * @property string|null $type 信息类型（如：candidate-候选人、participant-参与人、notifier-抄送人）
 * @property int|null $proc_inst_id 流程实例ID
 * @property int|null $userid 会员ID
 * @property int|null $msg_id 消息ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereProcInstId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkflowProcMsg whereUserid($value)
 * @mixin \Eloquent
 */
class WorkflowProcMsg extends AbstractModel
{

}
