<?php

namespace App\Module;

use Cache;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * 外网资源请求
 */
class Extranet
{
    /**
     * 通过 openAI 语音转文字
     * @param string $filePath
     * @return array
     */
    public static function openAItranscriptions($filePath)
    {
        if (!file_exists($filePath)) {
            return Base::retError("语音文件不存在");
        }
        $systemSetting = Base::setting('system');
        $aibotSetting = Base::setting('aibotSetting');
        if ($systemSetting['voice2text'] !== 'open' || empty($aibotSetting['openai_key'])) {
            return Base::retError("语音转文字功能未开启");
        }
        //
        $extra = [
            'Content-Type' => 'multipart/form-data',
            'Authorization' => 'Bearer ' . $aibotSetting['openai_key'],
        ];
        if ($aibotSetting['openai_agency']) {
            $extra['CURLOPT_PROXY'] = $aibotSetting['openai_agency'];
            if (str_contains($aibotSetting['openai_agency'], 'socks')) {
                $extra['CURLOPT_PROXYTYPE'] = CURLPROXY_SOCKS5;
            } else {
                $extra['CURLOPT_PROXYTYPE'] = CURLPROXY_HTTP;
            }
        }
        $res = Ihttp::ihttp_request('https://api.openai.com/v1/audio/transcriptions', [
            'file' => new \CURLFile($filePath),
            'model' => 'whisper-1'
        ], $extra, 15);
        if (Base::isError($res)) {
            return Base::retError("语音转文字失败", $res);
        }
        $resData = Base::json2array($res['data']);
        if (empty($resData['text'])) {
            return Base::retError("语音转文字失败", $resData);
        }
        //
        return Base::retSuccess("success", $resData['text']);
    }

    /**
     * 获取IP地址经纬度
     * @param string $ip
     * @return array
     */
    public static function getIpGcj02(string $ip = ''): array
    {
        if (empty($ip)) {
            $ip = Base::getIp();
        }
        $cacheKey = "getIpPoint::" . md5($ip);
        $result = Cache::rememberForever($cacheKey, function () use ($ip) {
            return Ihttp::ihttp_request("https://www.ifreesite.com/ipaddress/address.php?q=" . $ip, [], [], 12);
        });
        if (Base::isError($result)) {
            Cache::forget($cacheKey);
            return $result;
        }
        $data = $result['data'];
        $lastPos = strrpos($data, ',');
        $long = floatval(Base::getMiddle(substr($data, $lastPos + 1), null, ')'));
        $lat = floatval(Base::getMiddle(substr($data, strrpos(substr($data, 0, $lastPos), ',') + 1), null, ','));
        return Base::retSuccess("success", [
            'long' => $long,
            'lat' => $lat,
        ]);
    }

    /**
     * 百度接口：根据ip获取经纬度
     * @param string $ip
     * @return array
     */
    public static function getIpGcj02ByBaidu(string $ip = ''): array
    {
        if (empty($ip)) {
            $ip = Base::getIp();
        }

        $cacheKey = "getIpPoint::" . md5($ip);
        $result = Cache::rememberForever($cacheKey, function () use ($ip) {
            $ak = Config::get('app.baidu_app_key');
            $url = 'http://api.map.baidu.com/location/ip?ak=' . $ak . '&ip=' . $ip . '&coor=bd09ll';
            return Ihttp::ihttp_request($url, [], [], 12);
        });

        if (Base::isError($result)) {
            Cache::forget($cacheKey);
            return $result;
        }
        $data = json_decode($result['data'], true);

        // x坐标纬度, y坐标经度
        $long = Arr::get($data, 'content.point.x');
        $lat = Arr::get($data, 'content.point.y');
        return Base::retSuccess("success", [
            'long' => $long,
            'lat' => $lat,
        ]);
    }

    /**
     * 获取IP地址详情
     * @param string $ip
     * @return array
     */
    public static function getIpInfo(string $ip = ''): array
    {
        if (empty($ip)) {
            $ip = Base::getIp();
        }
        $cacheKey = "getIpInfo::" . md5($ip);
        $result = Cache::rememberForever($cacheKey, function () use ($ip) {
            return Ihttp::ihttp_request("http://ip.taobao.com/service/getIpInfo.php?accessKey=alibaba-inc&ip=" . $ip, [], [], 12);
        });
        if (Base::isError($result)) {
            Cache::forget($cacheKey);
            return $result;
        }
        $data = json_decode($result['data'], true);
        if (!is_array($data) || intval($data['code']) != 0) {
            Cache::forget($cacheKey);
            return Base::retError("error ip: -1");
        }
        $data = $data['data'];
        if (!is_array($data) || !isset($data['country'])) {
            return Base::retError("error ip: -2");
        }
        $data['text'] = $data['country'];
        $data['textSmall'] = $data['country'];
        if ($data['region'] && $data['region'] != $data['country'] && $data['region'] != "XX") {
            $data['text'] .= " " . $data['region'];
            $data['textSmall'] = $data['region'];
        }
        if ($data['city'] && $data['city'] != $data['region'] && $data['city'] != "XX") {
            $data['text'] .= " " . $data['city'];
            $data['textSmall'] .= " " . $data['city'];
        }
        if ($data['county'] && $data['county'] != $data['city'] && $data['county'] != "XX") {
            $data['text'] .= " " . $data['county'];
            $data['textSmall'] .= " " . $data['county'];
        }
        return Base::retSuccess("success", $data);
    }

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
     * 随机笑话接口
     * @return string
     */
    public static function randJoke(): string
    {
        $data = self::curl("https://hmajax.itheima.net/api/randjoke");
        $data = Base::json2array($data);
        if ($data['message'] === '获取成功' && $text = trim($data['data'])) {
            return $text;
        }
        return "";
    }

    /**
     * 心灵鸡汤
     * @return string
     */
    public static function soups(): string
    {
        $data = self::curl("https://hmajax.itheima.net/api/ambition");
        $data = Base::json2array($data);
        if ($data['message'] === '获取成功' && $text = trim($data['data'])) {
            return $text;
        }
        return "";
    }

    /**
     * 签到机器人网络内容
     * @param $type
     * @return string
     */
    public static function checkinBotQuickMsg($type): string
    {
        $text = "维护中...";
        switch ($type) {
            case "it":
                $data = self::curl('http://vvhan.api.hitosea.com/api/hotlist?type=itNews', 3600);
                if ($data = Base::json2array($data)) {
                    $i = 1;
                    $array = array_map(function ($item) use (&$i) {
                        if ($item['title'] && $item['desc']) {
                            return "<p>" . ($i++) . ". <strong><a href='{$item['mobilUrl']}' target='_blank'>{$item['title']}</a></strong></p><p>{$item['desc']}</p>";
                        } else {
                            return null;
                        }
                    }, $data['data']);
                    $array = array_values(array_filter($array));
                    if ($array) {
                        array_unshift($array, "<p><strong>{$data['title']}</strong>（{$data['update_time']}）</p>");
                        $text = implode("<p>&nbsp;</p>", $array);
                    }
                }
                break;

            case "36ke":
                $data = self::curl('http://vvhan.api.hitosea.com/api/hotlist?type=36Ke', 3600);
                if ($data = Base::json2array($data)) {
                    $i = 1;
                    $array = array_map(function ($item) use (&$i) {
                        if ($item['title'] && $item['desc']) {
                            return "<p>" . ($i++) . ". <strong><a href='{$item['mobilUrl']}' target='_blank'>{$item['title']}</a></strong></p><p>{$item['desc']}</p>";
                        } else {
                            return null;
                        }
                    }, $data['data']);
                    $array = array_values(array_filter($array));
                    if ($array) {
                        array_unshift($array, "<p><strong>{$data['title']}</strong>（{$data['update_time']}）</p>");
                        $text = implode("<p>&nbsp;</p>", $array);
                    }
                }
                break;

            case "60s":
                $data = self::curl('http://vvhan.api.hitosea.com/api/60s?type=json', 3600);
                if ($data = Base::json2array($data)) {
                    $i = 1;
                    $array = array_map(function ($item) use (&$i) {
                        if ($item) {
                            return "<p>" . ($i++) . ". {$item}</p>";
                        } else {
                            return null;
                        }
                    }, $data['data']);
                    $array = array_values(array_filter($array));
                    if ($array) {
                        array_unshift($array, "<p><strong>{$data['name']}</strong>（{$data['time'][0]}）</p>");
                        $text = implode("<p>&nbsp;</p>", $array);
                    }
                }
                break;

            case "joke":
                $text = "笑话被掏空";
                $data = self::curl('http://vvhan.api.hitosea.com/api/joke?type=json', 5);
                if ($data = Base::json2array($data)) {
                    if ($data = trim($data['joke'])) {
                        $text = "开心笑话：{$data}";
                    }
                }
                break;

            case "soup":
                $text = "鸡汤分完了";
                $data = self::curl('https://api.ayfre.com/jt/?type=bot', 5);
                if ($data) {
                    $text = "心灵鸡汤：{$data}";
                }
                break;
        }
        return $text;
    }

    /**
     * @param $url
     * @param int $cacheSecond 缓存时间（秒），如果结果为空则缓存有效30秒
     * @param int $timeout
     * @return string
     */
    private static function curl($url, int $cacheSecond = 0, int $timeout = 15): string
    {
        if ($cacheSecond > 0) {
            $key = "curlCache::" . md5($url);
            $content = Cache::remember($key, Carbon::now()->addSeconds($cacheSecond), function () use ($cacheSecond, $key, $timeout, $url) {
                $result = Ihttp::ihttp_request($url, [], [], $timeout);
                $content = Base::isSuccess($result) ? trim($result['data']) : '';
                if (empty($content) && $cacheSecond > 30) {
                    Cache::put($key, "", Carbon::now()->addSeconds(30));
                }
                return $content;
            });
        } else {
            $result = Ihttp::ihttp_request($url, [], [], $timeout);
            $content = Base::isSuccess($result) ? trim($result['data']) : '';
        }
        //
        return $content;
    }
}
