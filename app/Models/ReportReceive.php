<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ReportReceive
 *
 * @property int $id
 * @property int $rid
 * @property string|null $receive_time 接收时间
 * @property int $userid 接收人
 * @property int $read 是否已读
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive whereReceiveTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive whereRid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportReceive whereUserid($value)
 * @mixin \Eloquent
 */
class ReportReceive extends AbstractModel
{
    use HasFactory;

    // 关闭时间戳自动写入
    public $timestamps = false;

    protected $fillable = [
        "rid",
        "receive_time",
        "userid",
        "read",
    ];
}
