<?php

namespace App\Models;

/**
 * App\Models\UmengLog
 *
 * @property int $id
 * @property string|null $request 请求参数
 * @property string|null $response 推送返回
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog whereRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UmengLog extends AbstractModel
{
    protected $guarded = [];
}
