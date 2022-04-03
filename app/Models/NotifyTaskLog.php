<?php

namespace App\Models;

/**
 * App\Models\NotifyTaskLog
 *
 * @property int $id
 * @property int|null $rule_id 规则ID
 * @property int|null $task_id 任务ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTaskLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotifyTaskLog extends AbstractModel
{

}
