<?php


namespace App\Models;

use App\Module\Base;

/**
 * App\Models\UserCheckinRecord
 *
 * @property int $id
 * @property int|null $userid 会员id
 * @property string|null $mac MAC地址
 * @property string|null $date 签到日期
 * @property array $times 签到时间
 * @property int|null $report_time 上报的时间戳
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereMac($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereReportTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord whereUserid($value)
 * @mixin \Eloquent
 */
class UserCheckinRecord extends AbstractModel
{

    /**
     * 签到记录
     * @param $value
     * @return array
     */
    public function getTimesAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }

    /**
     * 时间收集
     * @return \Illuminate\Support\Collection
     */
    public function atCollect()
    {
        $sameTimes = array_map(function($time) {
            return [
                "datetime" => "{$this->date} {$time}",
                "timestamp" => strtotime("{$this->date} {$time}")
            ];
        }, $this->times);
        return collect($sameTimes);
    }

    /**
     * 签到时段
     * @param int $diff 多长未签到算失效（秒）
     * @return array
     */
    public function atSection($diff = 3600)
    {
        $start = "";
        $end = "";
        $array = [];
        foreach ($this->times as $time) {
            $time = preg_replace("/:00$/", "", $time);
            if (empty($start)) {
                $start = $time;
                continue;
            }
            if (empty($end)) {
                $end = $time;
                continue;
            }
            if (strtotime("2022-01-01 {$time}") - strtotime("2022-01-01 {$end}") > $diff) {
                $array[] = [$start, $end];
                $start = $time;
                $end = "";
                continue;
            }
            $end = $time;
        }
        if ($start) {
            $array[] = [$start, $end];
        }
        return $array;
    }
}
