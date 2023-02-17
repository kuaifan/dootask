<?php

require_once ("config.php");

/**
 * 有道翻译类
 */
class Youdao
{
    const URL = "https://openapi.youdao.com/api";
    const CURL_TIMEOUT = 10000;

    private string $APP_KEY = '';
    private string $SEC_KEY = '';

    /**
     * @param $APP_KEY
     * @param $SEC_KEY
     */
    public function __construct($APP_KEY, $SEC_KEY)
    {
        $this->APP_KEY = $APP_KEY;
        $this->SEC_KEY = $SEC_KEY;
    }

    /**
     * 翻译
     * @param $q
     * @param $from
     * @param $to
     * @return mixed|null
     */
    public function translate($q, $from = null, $to = null)
    {
        if ($from === null) {
            $from = 'auto';
        }
        if ($to === null) {
            $to = $from;
            $from = 'auto';
        }
        $salt = $this->create_guid();
        $args = array(
            'q' => $q,
            'appKey' => $this->APP_KEY,
            'salt' => $salt,
        );
        $args['from'] = $from;
        $args['to'] = $to;
        $args['signType'] = 'v3';
        $curtime = strtotime("now");
        $args['curtime'] = $curtime;
        $signStr = $this->APP_KEY . $this->truncate($q) . $salt . $curtime . $this->SEC_KEY;
        $args['sign'] = hash("sha256", $signStr);
        // $args['vocabId'] = '您的用户词表ID';
        $res = json_decode($this->call(self::URL, $args), true);
        if ($res['errorCode'] == 0 && $res['translation']) {
            return is_array($res['translation']) ? $res['translation'][0] : $res['translation'];
        }
        return null;
    }

    private function call($url, $args = null, $method = "post", $testflag = 0, $timeout = self::CURL_TIMEOUT, $headers = array())
    {
        $ret = false;
        $i = 0;
        while ($ret === false) {
            if ($i > 1)
                break;
            if ($i > 0) {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;
    }

    private function callOnce($url, $args = null, $method = "post", $withCookie = false, $timeout = self::CURL_TIMEOUT, $headers = array())
    {
        $ch = curl_init();
        $data = $this->convert($args);
        if ($method == "post") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            if ($data) {
                if (stripos($url, "?") > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    private function convert($args)
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . "&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }

    private function create_guid()
    {
        $microTime = microtime();
        list($a_dec, $a_sec) = explode(" ", $microTime);
        $dec_hex = dechex($a_dec * 1000000);
        $sec_hex = dechex($a_sec);
        $this->ensure_length($dec_hex, 5);
        $this->ensure_length($sec_hex, 6);
        $guid = $dec_hex;
        $guid .= $this->create_guid_section(3);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= $this->create_guid_section(6);
        return $guid;
    }

    private function create_guid_section($characters)
    {
        $return = "";
        for ($i = 0; $i < $characters; $i++) {
            $return .= dechex(mt_rand(0, 15));
        }
        return $return;
    }

    private function truncate($q)
    {
        $len = $this->abslength($q);
        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }

    private function abslength($str)
    {
        if (empty($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        } else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    private function ensure_length(&$string, $length)
    {
        $strlen = strlen($string);
        if ($strlen < $length) {
            $string = str_pad($string, $length, "0");
        } else if ($strlen > $length) {
            $string = substr($string, 0, $length);
        }
    }
}

try {
    // 读取文件
    if (!file_exists("content.txt")) {
        throw new Exception("content.txt file does not exist");
    }
    $content = file_get_contents("content.txt");
    $arr = explode("\n", $content);
    $news = [];
    $keys = [];
    if (file_exists("content.json")) {
        $tmps = json_decode(file_get_contents("content.json"), true);
        foreach ($tmps as $tmp) {
            if (!isset($tmp['key'])) {
                continue;
            }
            $news[] = $tmp;
            $keys[] = $tmp['key'];
        }
    }
    // 提取要翻译的
    $needs = [];
    foreach ($arr as $item) {
        $item = trim($item);
        if ($tmp = json_decode($item, true)) {
            $key = key($tmp);
            $val = current($tmp);
        } else {
            $key = $val = $item;
        }
        if (in_array($key, $keys)) {
            continue;
        }
        $needs[$key] = $val;
    }
    $needs = array_filter($needs);
    $waits = array_chunk($needs, 200, true);
    // 分组翻译
    $YD = new Youdao(YOUDAO_APP_KEY, YOUDAO_SEC_KEY);
    $func = function($text) {
        if (!$text) {
            return null;
        }
        return preg_replace_callback("/^\(\*\)\s*[A-Z]/", function ($match) {
            return strtolower($match[0]);
        }, ucfirst($text));
    };
    foreach ($waits as $items) {
        $text = implode("\n", $items);
        $TCS = explode("\n", $YD->translate($text, "zh-CHS", "zh-CHT"));    // 繁体
        $ENS = explode("\n", $YD->translate($text, "zh-CHS", "en"));        // 英语
        $KOS = explode("\n", $YD->translate($text, "zh-CHS", "ko"));        // 韩语
        $JAS = explode("\n", $YD->translate($text, "zh-CHS", "ja"));        // 日语
        $DES = explode("\n", $YD->translate($text, "zh-CHS", "de"));        // 德语
        $FRS = explode("\n", $YD->translate($text, "zh-CHS", "fr"));        // 法语
        $IDS = explode("\n", $YD->translate($text, "zh-CHS", "id"));        // 印度尼西亚
        $index = 0;
        foreach ($items as $key => $item) {
            $tmp = [];
            $tmp['key'] = $key;
            $tmp['zh-CN'] = $key != $item ? $item : "";
            $tmp["zh-CHT"] = $func($TCS[$index]);
            $tmp["en"] = $func($ENS[$index]);
            $tmp["ko"] = $func($KOS[$index]);
            $tmp["ja"] = $func($JAS[$index]);
            $tmp["de"] = $func($DES[$index]);
            $tmp["fr"] = $func($FRS[$index]);
            $tmp["id"] = $func($IDS[$index]);
            $news[] = $tmp;
            $index++;
        }
    }
    // 按长度排序
    $inOrder = [];
    foreach ($news as $index => $item) {
        $key = $item['key'];
        if (str_contains($key, '(*)')) {
            $inOrder[$index] = strlen($key);
        } else {
            $inOrder[$index] = strlen($key) + 10000000000;
        }
    }
    array_multisort($inOrder, SORT_DESC, $news);
    // 合成数组
    $arr = ['key' => []];
    $index = 0;
    foreach ($news as $items) {
        $arr['key'][$items['key']] = $index++;
        foreach ($items as $key => $item) {
            if ($key === 'key') {
                continue;
            }
            if (!isset($arr)) {
                $arr[$key] = [];
            }
            $arr[$key][] = $item;
        }
    }
    // 写入新文件
    if (!is_dir("../public/js/language")) {
        mkdir("../public/js/language", 0777, true);
    }
    foreach ($arr as $key => $item) {
        $file = "../public/js/language/" . $key . ".js";
        file_put_contents($file, "if(typeof window.LANGUAGE_DATA===\"undefined\")window.LANGUAGE_DATA={};window.LANGUAGE_DATA[\"{$key}\"]=" . json_encode($item, JSON_UNESCAPED_UNICODE));
        print_r($file . " saved\n");
    }
    file_put_contents("content.json", json_encode(array_values($news), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    print_r("Translate success\ntotal: " . count($news) . "\nadd: " . count($needs) . "\n");
} catch (Exception $e) {
    print_r("Error, " . $e->getMessage());
}

