<?php

namespace App\Module;

use Cache;
use Carbon\Carbon;

/**
 * 外网资源请求
 */
class Extranet
{
    /**
     * 判断是否工作日
     * @param string $Ymd 年月日（如：20220102）
     * @return int
     * 0: 工作日
     * 1: 非工作日
     * 2: 获取不到远程数据的非工作日（周六、日）
     * 所以可以用>0来判断是否工作日
     */
    public static function isHoliday(string $Ymd): int
    {
        $time = strtotime($Ymd . " 00:00:00");
        $holidayKey = "holiday::" . date("Ym", $time);
        $holidayData = Cache::remember($holidayKey, now()->addMonth(), function () use ($time) {
            $apiMonth = date("Ym", $time);
            $apiResult = Ihttp::ihttp_request("https://api.apihubs.cn/holiday/get?field=date&month={$apiMonth}&workday=2&size=31", [], [], 20);
            if (Base::isError($apiResult)) {
                info('[holiday] get error');
                return [];
            }
            $apiResult = Base::json2array($apiResult['data']);
            if ($apiResult['code'] !== 0) {
                info('[holiday] result error');
                return [];
            }
            return array_map(function ($item) {
                return $item['date'];
            }, $apiResult['data']['list']);
        });
        if (empty($holidayData)) {
            Cache::forget($holidayKey);
            return in_array(date("w", $time), [0, 6]) ? 2 : 0;
        }
        return in_array($Ymd, $holidayData) ? 1 : 0;
    }

    /**
     * Drawio 图标搜索
     * @param $query
     * @param $page
     * @param $size
     * @return array
     */
    public static function drawioIconSearch($query, $page, $size): array
    {
        $result = self::curl("https://app.diagrams.net/iconSearch?q={$query}&p={$page}&c={$size}", 15 * 86400);
        if ($result = Base::json2array($result)) {
            return $result;
        }
        return [
            'icons' => [],
            'total_count' => 0
        ];
    }

    /**
     * 获取搜狗表情包
     * @param $keyword
     * @return array
     */
    public static function sticker($keyword)
    {
        $data = self::curl("https://pic.sogou.com/napi/wap/searchlist", 1800, 15, [], [
            'CURLOPT_CUSTOMREQUEST' => 'POST',
            'CURLOPT_POSTFIELDS' => json_encode([
                "initQuery" => $keyword . " 表情",
                "queryFrom" => "wap",
                "ie" => "utf8",
                "keyword" => $keyword . " 表情",
                // "mode" => 20,
                "showMode" => 0,
                "start" => 1,
                "reqType" => "client",
                "reqFrom" => "wap_result",
                "prevIsRedis" => "n",
                "pagetype" => 0,
                "amsParams" => []
            ]),
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json',
                'Referer: https://pic.sogou.com/'
            ]
        ]);
        $data = Base::json2array($data);
        if ($data['status'] === 0 && $data['data']['picResult']['items']) {
            $data = $data['data']['picResult']['items'];
            $data = array_filter($data, function ($item) {
                return intval($item['thumbHeight']) > 10 && intval($item['thumbWidth']) > 10;
            });
            return array_map(function ($item) {
                return [
                    'name' => $item['title'],
                    'src' => $item['thumbUrl'],
                    'height' => $item['thumbHeight'],
                    'width' => $item['thumbWidth'],
                ];
            }, $data);
        }
        return [];
    }

    /**
     * @param $url
     * @param int $cacheSecond 缓存时间（秒），如果结果为空则缓存有效30秒
     * @param int $timeout
     * @param array $post
     * @param array $extra
     * @return string
     */
    private static function curl($url, int $cacheSecond = 0, int $timeout = 15, array $post = [], array $extra = []): string
    {
        if ($cacheSecond > 0) {
            $key = "curlCache::" . md5($url) . "::" . md5(json_encode($post)) . "::" . md5(json_encode($extra));
            $content = Cache::remember($key, Carbon::now()->addSeconds($cacheSecond), function () use ($extra, $post, $cacheSecond, $key, $timeout, $url) {
                $result = Ihttp::ihttp_request($url, $post, $extra, $timeout);
                $content = Base::isSuccess($result) ? trim($result['data']) : '';
                if (empty($content) && $cacheSecond > 30) {
                    Cache::put($key, "", Carbon::now()->addSeconds(30));
                }
                return $content;
            });
        } else {
            $result = Ihttp::ihttp_request($url, $post, $extra, $timeout);
            $content = Base::isSuccess($result) ? trim($result['data']) : '';
        }
        //
        return $content;
    }
}
