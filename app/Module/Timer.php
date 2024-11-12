<?php

namespace App\Module;

use App\Services\RequestContext;
use Carbon\Carbon;

class Timer
{
    /**
     * 获取时间戳
     * @return int
     */
    public static function time()
    {
        return intval(RequestContext::get("start_time", time()));
    }

    /**
     * 获取毫秒时间戳
     * @return float
     */
    public static function msecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $time = explode(".", $sec . ($msec * 1000));
        return $time[0];
    }

    /**
     * 时间差(不够1个小时算一个小时)
     * @param int $s 开始时间戳
     * @param int $e 结束时间戳
     * @return string
     */
    public static function timeDiff($s, $e)
    {
        $time = $e - $s;
        $days = 0;
        if ($time >= 86400) { // 如果大于1天
            $days = (int)($time / 86400);
            $time = $time % 86400; // 计算天后剩余的毫秒数
        }
        $hours = 0;
        if ($time >= 3600) { // 如果大于1小时
            $hours = (int)($time / 3600);
            $time = $time % 3600; // 计算小时后剩余的毫秒数
        }
        $minutes = ceil($time / 60); // 剩下的毫秒数都算作分
        $daysStr = $days > 0 ? $days . '天' : '';
        $hoursStr = ($hours > 0 || ($days > 0 && $minutes > 0)) ? $hours . '时' : '';
        $minuteStr = ($minutes > 0) ? $minutes . '分' : '';
        return $daysStr . $hoursStr . $minuteStr;
    }

    /**
     * 时间秒数格式化
     * @param int $time 时间秒数
     * @return string
     */
    public static function timeFormat($time)
    {
        $days = 0;
        if ($time >= 86400) { // 如果大于1天
            $days = (int)($time / 86400);
            $time = $time % 86400; // 计算天后剩余的毫秒数
        }
        $hours = 0;
        if ($time >= 3600) { // 如果大于1小时
            $hours = (int)($time / 3600);
            $time = $time % 3600; // 计算小时后剩余的毫秒数
        }
        $minutes = ceil($time / 60); // 剩下的毫秒数都算作分
        $daysStr = $days > 0 ? $days . '天' : '';
        $hoursStr = ($hours > 0 || ($days > 0 && $minutes > 0)) ? $hours . '时' : '';
        $minuteStr = ($minutes > 0) ? $minutes . '分' : '';
        return $daysStr . $hoursStr . $minuteStr;
    }

    /**
     * 检测日期格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isDate($str)
    {
        $strArr = explode('-', $str);
        if (empty($strArr) || count($strArr) != 3) {
            return false;
        } else {
            list($year, $month, $day) = $strArr;
            if (checkdate(intval($month), intval($day), intval($year))) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 检测时间格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isTime($str)
    {
        $strArr = explode(':', $str);
        $count = count($strArr);
        if ($count < 2 || $count > 3) {
            return false;
        }
        $hour = $strArr[0];
        if ($hour < 0 || $hour > 23) {
            return false;
        }
        $minute = $strArr[1];
        if ($minute < 0 || $minute > 59) {
            return false;
        }
        if ($count == 3) {
            $second = $strArr[2];
            if ($second < 0 || $second > 59) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检测 日期格式 或 时间格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isDateOrTime($str)
    {
        return self::isDate($str) || self::isTime($str);
    }

    /**
     * 时间转毫秒时间戳
     * @param $time
     * @return float|int
     */
    public static function strtotimeM($time)
    {
        if (str_contains($time, '.')) {
            list($t, $m) = explode(".", $time);
            if (is_string($t)) {
                $t = strtotime($t);
            }
            $time = $t . str_pad($m, 3, "0", STR_PAD_LEFT);
        }
        if (is_numeric($time)) {
            return (int) str_pad($time, 13, "0");
        } else {
            return strtotime($time) * 1000;
        }
    }

    /**
     * 时间格式化
     * @param $date
     * @return false|string
     */
    public static function forumDate($date)
    {
        $dur = time() - $date;
        if ($date > Carbon::now()->startOf('day')->timestamp) {
            //今天
            if ($dur < 60) {
                return max($dur, 1) . '秒前';
            } elseif ($dur < 3600) {
                return floor($dur / 60) . '分钟前';
            } elseif ($dur < 86400) {
                return floor($dur / 3600) . '小时前';
            } else {
                return date("H:i", $date);
            }
        } elseif ($date > Carbon::now()->subDays()->startOf('day')->timestamp) {
            //昨天
            return '昨天';
        } elseif ($date > Carbon::now()->subDays(2)->startOf('day')->timestamp) {
            //前天
            return '前天';
        } elseif ($dur > 86400) {
            //x天前
            return floor($dur / 86400) . '天前';
        }
        return date("Y-m-d", $date);
    }

    /**
     * 获取(时间戳转)今天是星期几，只返回（几）
     * @param string|number $unixTime
     * @return string
     */
    public static function getWeek($unixTime = '')
    {
        $unixTime = is_numeric($unixTime) ? $unixTime : time();
        $weekarray = ['日', '一', '二', '三', '四', '五', '六'];
        return $weekarray[date('w', $unixTime)];
    }

    /**
     * 获取(时间戳转)现在时间段：深夜、凌晨、早晨、上午.....
     * @param string|number $unixTime
     * @return string
     */
    public static function getDayeSegment($unixTime = '')
    {
        $unixTime = is_numeric($unixTime) ? $unixTime : time();
        $H = date('H', $unixTime);
        if ($H >= 19) {
            return '晚上';
        } elseif ($H >= 18) {
            return '傍晚';
        } elseif ($H >= 13) {
            return '下午';
        } elseif ($H >= 12) {
            return '中午';
        } elseif ($H >= 8) {
            return '上午';
        } elseif ($H >= 5) {
            return '早晨';
        } elseif ($H >= 1) {
            return '凌晨';
        } elseif ($H >= 0) {
            return '深夜';
        } else {
            return '';
        }
    }

    /**
     * 秒 （转） 年、天、时、分、秒
     * @param $time
     * @return array|bool
     */
    public static function sec2time($time)
    {
        if (is_numeric($time)) {
            $value = array(
                "years" => 0, "days" => 0, "hours" => 0,
                "minutes" => 0, "seconds" => 0,
            );
            if ($time >= 86400) {
                $value["days"] = floor($time / 86400);
                $time = ($time % 86400);
            }
            if ($time >= 3600) {
                $value["hours"] = floor($time / 3600);
                $time = ($time % 3600);
            }
            if ($time >= 60) {
                $value["minutes"] = floor($time / 60);
                $time = ($time % 60);
            }
            $value["seconds"] = floor($time);
            return (array)$value;
        } else {
            return (bool)FALSE;
        }
    }

    /**
     * 年、天、时、分、秒 （转） 秒
     * @param $value
     * @return int
     */
    public static function time2sec($value)
    {
        $time = intval($value["seconds"]);
        $time += intval($value["minutes"] * 60);
        $time += intval($value["hours"] * 3600);
        $time += intval($value["days"] * 86400);
        $time += intval($value["years"] * 31536000);
        return $time;
    }

    /**
     * 阿拉伯数字转化为中文
     * @param $num
     * @return string
     */
    public static function chinaNum($num)
    {
        $china = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $arr = str_split($num);
        $txt = '';
        for ($i = 0; $i < count($arr); $i++) {
            $txt .= $china[$arr[$i]];
        }
        return $txt;
    }

    /**
     * 阿拉伯数字转化为中文（用于星期，七改成日）
     * @param $num
     * @return string
     */
    public static function chinaNumZ($num)
    {
        return str_replace("七", "日", Timer::chinaNum($num));
    }

    /**
     * 时间是否在时间范围内
     * @param array $timeRanges 如：['08:00', '12:00'] 或 [['08:00', '12:00'], ['14:00', '18:00']]
     * @param string|null $currentTime
     * @return bool
     */
    public static function isTimeInRanges(array $timeRanges, ?string $currentTime = null): bool
    {
        // 如果没有传入当前时间，使用当前时间
        $currentTime = $currentTime ?? date('H:i');

        // 转换当前时间为分钟数，便于比较
        $currentMinutes = self::timeToMinutes($currentTime);
        if ($currentMinutes === false) {
            return false;
        }

        // 将单个时间范围转换为数组格式
        if (isset($timeRanges[0]) && !is_array($timeRanges[0])) {
            $timeRanges = [$timeRanges];
        }

        // 过滤并检查有效的时间范围
        foreach ($timeRanges as $range) {
            if (!self::isValidTimeRange($range)) {
                continue;
            }

            $startMinutes = self::timeToMinutes($range[0]);
            $endMinutes = self::timeToMinutes($range[1]);

            if ($startMinutes === false || $endMinutes === false) {
                continue;
            }

            if ($startMinutes <= $currentMinutes && $currentMinutes <= $endMinutes) {
                return true;
            }
        }

        return false;
    }

    /**
     * 辅助函数：检查时间范围是否有效
     * @param $range
     * @return bool
     */
    private static function isValidTimeRange($range): bool
    {
        return is_array($range)
            && count($range) === 2
            && is_string($range[0])
            && is_string($range[1])
            && !empty($range[0])
            && !empty($range[1])
            && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $range[0])
            && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $range[1]);
    }

    /**
     * 辅助函数：将时间转换为分钟数
     * @param string $time
     * @return false|float|int
     */
    private static function timeToMinutes(string $time)
    {
        if (!preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $time, $matches)) {
            return false;
        }

        return intval($matches[1]) * 60 + intval($matches[2]);
    }

}
