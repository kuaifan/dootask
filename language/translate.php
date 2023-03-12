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
     * @param $successSleep
     * @return mixed|null
     * @throws Exception
     */
    public function translate($q, $from = null, $to = null, $successSleep = 1)
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
        $data = $this->call(self::URL, $args);
        $res = json_decode($data, true);
        if ($res['errorCode'] == 0 && $res['translation']) {
            sleep($successSleep);
            return is_array($res['translation']) ? $res['translation'][0] : $res['translation'];
        }
        file_put_contents('error.log', $data . "\n", FILE_APPEND);
        throw new Exception("翻译失败，详细查看：error.log\n\n");
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
    // 译文
    $translations = [];
    if (file_exists( "translate.json")) {
        $tmps = json_decode(file_get_contents("translate.json"), true);
        foreach ($tmps as $tmp) {
            if (!isset($tmp['key'])) {
                continue;
            }
            $translations[$tmp['key']] = $tmp;
        }
    }
    foreach (['api', 'web'] as $type) {
        // 读取文件
        $content = file_exists("original-{$type}.txt") ? file_get_contents("original-{$type}.txt") : "";
        $array = array_values(array_filter(array_unique(explode("\n", $content))));
        // 提取要翻译的
        $datas = [];
        $needs = [];
        foreach ($array as $text) {
            $text = trim($text);
            if ($tmp = json_decode($text, true)) {
                $key = key($tmp);
                $value = current($tmp);
            } else {
                $key = $value = $text;
            }
            if (isset($translations[$key])) {
                $datas[] = $translations[$key];
            } else {
                $needs[$key] = $value;
            }
        }
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
                $tmp['zh'] = $key != $item ? $item : "";
                $tmp["zh-CHT"] = $func($TCS[$index]);
                $tmp["en"] = $func($ENS[$index]);
                $tmp["ko"] = $func($KOS[$index]);
                $tmp["ja"] = $func($JAS[$index]);
                $tmp["de"] = $func($DES[$index]);
                $tmp["fr"] = $func($FRS[$index]);
                $tmp["id"] = $func($IDS[$index]);
                $datas[] = $translations[$key] = $tmp;
                $index++;
            }
        }
        // 按长度排序
        $inOrder = [];
        foreach ($datas as $index => $item) {
            $key = $item['key'];
            if (str_contains($key, '(*)')) {
                $inOrder[$index] = strlen($key);
            } else {
                $inOrder[$index] = strlen($key) + 10000000000;
            }
        }
        array_multisort($inOrder, SORT_DESC, $datas);
        // 合成数组
        $results = ['key' => []];
        $index = 0;
        foreach ($datas as $items) {
            $results['key'][$items['key']] = $index++;
            foreach ($items as $key => $item) {
                if ($key === 'key') {
                    continue;
                }
                if (!isset($results)) {
                    $results[$key] = [];
                }
                $results[$key][] = $item;
            }
        }
        // 生成文件
        if ($type === 'api') {
            if (!is_dir("../public/language/api")) {
                mkdir("../public/language/api", 0777, true);
            }
            foreach ($results as $key => $item) {
                $file = "../public/language/api/$key.json";
                file_put_contents($file, json_encode($item, JSON_UNESCAPED_UNICODE));
            }
        } elseif ($type === 'web') {
            if (!is_dir("../public/language/web")) {
                mkdir("../public/language/web", 0777, true);
            }
            foreach ($results as $key => $item) {
                $file = "../public/language/web/$key.js";
                file_put_contents($file, "if(typeof window.LANGUAGE_DATA===\"undefined\")window.LANGUAGE_DATA={};window.LANGUAGE_DATA[\"{$key}\"]=" . json_encode($item, JSON_UNESCAPED_UNICODE));
            }
        }
        print_r("[$type] translate success\ntotal: " . count($results['key']) . "\nadd: " . count($needs) . "\n\n");
    }
    file_put_contents("translate.json", json_encode(array_values($translations), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

} catch (Exception $e) {
    print_r("[$type] error, " . $e->getMessage());
}

