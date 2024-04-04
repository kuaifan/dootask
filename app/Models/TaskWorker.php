<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\TaskWorker
 *
 * @property int $id
 * @property string|null $args
 * @property string|null $error
 * @property string|null $start_at 开始时间
 * @property string|null $end_at 结束时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereArgs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskWorker withoutTrashed()
 * @mixin \Eloquent
 */
class TaskWorker extends AbstractModel
{
    use SoftDeletes;
}
