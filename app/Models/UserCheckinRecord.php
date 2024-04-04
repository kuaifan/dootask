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
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
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
     * 获取签到时间
     * @param int $userid
     * @param array $betweenTimes
     * @return array
     */
    public static function getTimes(int $userid, array $betweenTimes)
    {
        $array = [];
        $records = self::whereUserid($userid)->whereBetween('created_at', $betweenTimes)->orderBy('id')->get();
        /** @var self $record */
        foreach ($records as $record) {
            $times = array_map(function ($time) {
                return preg_replace("/(\d+):(\d+):\d+$/", "$1:$2", $time);
            }, $record->times);
            if (isset($array[$record->date])) {
                $array[$record->date] = array_merge($array[$record->date], $times);
            } else {
                $array[$record->date] = $times;
            }
        }
        //
        foreach ($array as $date => $times) {
            $times = array_values(array_filter(array_unique($times)));
            $inOrder = [];
            foreach ($times as $key => $time) {
                $inOrder[$key] = strtotime("2022-01-01 {$time}");
            }
            array_multisort($inOrder, SORT_ASC, $times);
            $array[$date] = $times;
        }
        //
        return $array;
    }

    /**
     * 时间收集
     * @param string $data
     * @param array $times
     * @return \Illuminate\Support\Collection
     */
    public static function atCollect($data, $times)
    {
        $sameTimes = array_map(function($time) use ($data) {
            return [
                "datetime" => "{$data} {$time}",
                "timestamp" => strtotime("{$data} {$time}")
            ];
        }, $times);
        return collect($sameTimes);
    }

    /**
     * 签到时段
     * @param array $times
     * @param int $diff 多长未签到算失效（秒）
     * @return array
     */
    public static function atSection($times, $diff = 3600)
    {
        $start = "";
        $end = "";
        $array = [];
        foreach ($times as $time) {
            $time = preg_replace("/(\d+):(\d+):\d+$/", "$1:$2", $time);
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
