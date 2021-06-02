<?php

namespace App\Models;

/**
 * Class ProjectTaskMsg
 *
 * @package App\Models
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property string|null $msg 详细内容(JSON)
 * @property int|null $userid 发送用户ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskMsg whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskMsg extends AbstractModel
{

}
