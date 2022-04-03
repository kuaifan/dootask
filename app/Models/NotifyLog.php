<?php

namespace App\Models;

/**
 * App\Models\NotifyLog
 *
 * @property int $id
 * @property int|null $rule_id 规则ID
 * @property int|null $userid 会员ID
 * @property string|null $vars 内容变量
 * @property string|null $content 详细内容
 * @property string|null $error 错误详情
 * @property int|null $success 是否成功
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereSuccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyLog whereVars($value)
 * @mixin \Eloquent
 */
class NotifyLog extends AbstractModel
{

}
