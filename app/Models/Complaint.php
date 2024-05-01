<?php

namespace App\Models;


/**
 * App\Models\Complaint
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $userid 举报人id
 * @property int|null $type 举报类型
 * @property string|null $reason 举报原因
 * @property string|null $imgs 举报图片
 * @property int|null $status 状态 0待处理、1已处理、2已删除
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereImgs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Complaint whereUserid($value)
 * @mixin \Eloquent
 */
class Complaint extends AbstractModel
{

}
