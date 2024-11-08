<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\Setting;
use App\Models\Tmp;
use Cache;
use Carbon\Carbon;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Overtrue\Pinyin\Pinyin;
use Redirect;
use Request;
use Response;
use Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Validator;

class Base
{

    /**
     * 访问日志
     * @param string $title
     */
    public static function addTmp($title = '')
    {
        if (!config('app.debug')) {
            return;
        }
        self::addLog([
            'url' => Request::url(),
            'fullUrl' => Request::fullUrl(),
            'requestUri' => Request::getRequestUri(),
            'uri' => Request::getUri(),
            'ip' => Base::getIp(),
            'header' => Request::header(),
            'input' => Request::input(),
            'query' => Request::query(),
            'post' => Request::post(),
            'time' => date("Y-m-d H:i:s", time())
        ], $title);
    }

    /**
     * 添加日志
     * @param string|array $log
     * @param string $title
     */
    public static function addLog($log, $title = '')
    {
        if (!config('app.debug') || defined('DO_NOT_ADD_LOGS')) {
            return;
        }
        if (is_array($log)) {
            $log = self::array2json($log, JSON_UNESCAPED_UNICODE);
        }
        Tmp::createInstance([
            'name' => 'log_' . ($title ?: date("Y-m-d H:i:s", time())),
            'value' => date("Y-m-d H:i:s", time()),
            'content' => $log,
        ])->save();
    }

    /**
     * 获取package配置文件
     * @return array
     */
    public static function getPackage()
    {
        return Cache::remember("Base::package", now()->addSeconds(10), function () {
            $file = base_path('package.json');
            if (file_exists($file)) {
                $package = json_decode(file_get_contents($file), true);
                return is_array($package) ? $package : [];
            }
            return [];
        });
    }

    /**
     * 获取token
     * @return mixed|string
     */
    public static function token()
    {
        return Base::headerOrInput('dootask-token') ?: Base::headerOrInput('token');
    }

    /**
     * 如果header没有则通过input读取
     * @param $key
     * @return mixed|string
     */
    public static function headerOrInput($key)
    {
        return Base::nullShow(Request::header($key), Request::input($key));
    }

    /**
     * 如果input没有则通过header读取
     * @param $key
     * @return mixed|string
     */
    public static function inputOrHeader($key)
    {
        return Base::nullShow(Request::input($key), Request::header($key));
    }

    /**
     * 获取版本号
     * @return string
     */
    public static function getVersion()
    {
        $package = self::getPackage();
        return $package['version'] ?? '1.0.0';
    }

    /**
     * 获取客户端版本号
     * @return string
     */
    public static function getClientVersion()
    {
        global $_A;
        if (!isset($_A["__static_client_version"])) {
            $_A["__static_client_version"] = self::headerOrInput('version') ?: '0.0.1';
        }
        return $_A["__static_client_version"];
    }

    /**
     * 检查客户端版本
     * @param string $min 最小版本
     * @return void
     */
    public static function checkClientVersion($min)
    {
        if (!self::judgeClientVersion($min)) {
            throw new ApiException('当前客户端版本 (' . Base::getClientVersion() . ') 过低，最低版本要求 (' . $min . ')。');
        }
    }

    /**
     * 判断客户端版本
     * @param $min // 最小版本（满足此版本返回true）
     * @param null $clientVersion
     * @return bool
     */
    public static function judgeClientVersion($min, $clientVersion = null)
    {
        return !version_compare($clientVersion ?: Base::getClientVersion(), $min, '<');
    }

    /**
     * 判断是否域名格式
     * @param $domain
     * @return bool
     */
    public static function is_domain($domain){
        $str = "/^(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if (!preg_match($str, $domain)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 判断字符串是否IP获取子掩码IP
     * @param $cidr
     * @return bool
     */
    public static function is_cidr($cidr)
    {
        if (str_contains($cidr, '/')) {
            list($cidr, $netmask) = explode('/', $cidr, 2);
            if ($netmask > 32 || $netmask < 0 || trim($netmask) == '') {
                return false;
            }
        }
        return filter_var($cidr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * 判断IP是否正确
     * @param string $ip
     * @return bool
     */
    public static function is_ipv4($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * 判断是否外网IP
     * @param string $ip
     * @return bool
     */
    public static function is_extranet_ip($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        return !self::is_internal_ip($ip);
    }

    /**
     * 判断是否内网IP
     * @param string $ip
     * @return bool
     */
    public static function is_internal_ip($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $ip = ip2long($ip);
        if (!$ip) {
            return false;
        }
        $net_l = ip2long('127.255.255.255') >> 24;          //127.x.x.x
        $net_a = ip2long('10.255.255.255') >> 24;           //A类网预留ip的网络地址
        $net_b = ip2long('172.31.255.255') >> 20;           //B类网预留ip的网络地址
        $net_c = ip2long('192.168.255.255') >> 16;          //C类网预留ip的网络地址
        return $ip >> 24 === $net_l || $ip >> 24 === $net_a || $ip >> 20 === $net_b || $ip >> 16 === $net_c;
    }

    /**
     * 获取数组值
     * @param $obj
     * @param string $key
     * @param string $default
     * @return array|string
     */
    public static function val($obj, $key = '', $default = '')
    {
        if (!is_array($obj)) {
            return $default;
        }
        if (is_int($key)) {
            if (isset($obj[$key])) {
                $obj = $obj[$key];
            } else {
                $obj = "";
            }
        } elseif (!empty($key)) {
            $arr = explode(".", str_replace("|", ".", $key));
            foreach ($arr as $val) {
                if (isset($obj[$val])) {
                    $obj = $obj[$val];
                } else {
                    $obj = "";
                    break;
                }
            }
        }
        if ($default && empty($obj)) $obj = $default;
        return $obj;
    }

    /**
     * 获取当前uri
     * @return string
     */
    public static function getUrl()
    {
        return Request::getRequestUri();
    }

    /**
     * 跳转
     * @param null $url
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function goUrl($url = null)
    {
        if (empty($url)) {
            $url = Base::getUrl();
        }
        return Redirect::to($url, 301);
    }

    /**
     * 默认显示
     * @param $str
     * @param $val
     * @param $default
     * @return mixed
     */
    public static function nullShow($str, $val, $default = '')
    {
        return $str ? ($default ?: $str) : $val;
    }

    /**
     * 补零
     * @param $str
     * @param int $length 长度
     * @param bool $before 是否补在前面
     * @return string
     */
    public static function zeroFill($str, $length = 0, $before = true)
    {
        if (strlen($str) >= $length) {
            return $str;
        }
        $_str = '';
        for ($i = 0; $i < $length; $i++) {
            $_str .= '0';
        }
        if ($before) {
            $_ret = substr($_str . $str, $length * -1);
        } else {
            $_ret = substr($str . $_str, 0, $length);
        }
        return $_ret;
    }

    /**
     * 新建文件夹
     * @param $path
     * @return mixed
     */
    public static function makeDir($path)
    {
        try {
            Storage::makeDirectory($path);
        } catch (\Throwable $e) {
        }
        if (!file_exists($path)) {
            self::makeDir(dirname($path));
            @mkdir($path);
            @chmod($path, 0777);
        }
        return $path;
    }

    /**
     * 删除文件夹
     * @param $path
     */
    public static function deleteDir($path)
    {
        Storage::deleteDirectory($path);
    }

    /**
     * 删除文件夹及文件夹下所有的文件
     * @param $dirName
     * @param bool $undeleteDir 不删除文件夹（只删除文件）
     */
    public static function deleteDirAndFile($dirName, $undeleteDir = false)
    {
        if ($handle = opendir($dirName)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir($dirName . "/" . $item)) {
                        self::deleteDirAndFile($dirName . "/" . $item);
                    } else {
                        @unlink($dirName . "/" . $item);
                    }
                }
            }
            closedir($handle);
            if ($undeleteDir === false) {
                rmdir($dirName);
            }
        }
    }

    /**
     *
     * 截取字符串
     * @param string $str 字符串
     * @param int $length 截取长度
     * @param int $start 何处开始
     * @param string $suffix 后缀（超出长度显示，默认：...）
     * @return string
     */
    public static function cutStr(string $str, int $length, int $start = 0, string $suffix = '...')
    {
        $strLen = mb_strlen($str);
        // 处理负数长度
        if ($length < 0) {
            $length = max($strLen + $length, 0);
        }
        // 处理负数起始位置
        if ($start < 0) {
            $start = max($strLen + $start, 0);
        }
        // 处理边界情况
        if ($length === 0 || $start >= $strLen) {
            return '';
        }
        $result = mb_substr($str, $start, $length);
        // 只有当实际截取的长度小于原字符串长度时才添加后缀
        if ($start + $length < $strLen) {
            return $result . $suffix;
        }
        return $result;
    }

    /**
     * 将字符串转换为数组
     * @param string $data 字符串
     * @param array $default 为空时返回的默认数组
     * @return    array    返回数组格式，如果，data为空，则返回$default
     */
    public static function string2array($data, $default = [])
    {
        if (is_array($data)) {
            return $data ?: $default;
        }
        $data = trim($data);
        if ($data == '') return $default;
        if (str_starts_with(strtolower($data), 'array') && strtolower($data) !== 'array') {
            @ini_set('display_errors', 'on');
            @eval("\$array = $data;");
            @ini_set('display_errors', 'off');
        } else {
            if (str_starts_with($data, '{\\')) {
                $data = stripslashes($data);
            }
            $array = json_decode($data, true);
        }
        return isset($array) && is_array($array) && $data ? $array : $default;
    }

    /**
     * 将数组转换为字符串
     * @param array $data 数组
     * @param int $isformdata 如果为0，则不使用new_stripslashes处理，可选参数，默认为1
     * @return    string    返回字符串，如果，data为空，则返回空
     */
    public static function array2string($data, $isformdata = 1)
    {
        if ($data == '' || empty($data)) return '';
        if ($isformdata) $data = Base::newStripslashes($data);
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            return Base::newAddslashes(json_encode($data));
        } else {
            return Base::newAddslashes(json_encode($data, JSON_FORCE_OBJECT));
        }
    }

    /**
     * 将数组转换为字符串 (格式化)
     * @param array $data 数组
     * @param int $isformdata 如果为0，则不使用new_stripslashes处理，可选参数，默认为1
     * @return    string    返回字符串，如果，data为空，则返回空
     */
    public static function array2string_discard($data, $isformdata = 1)
    {
        if ($data == '' || empty($data)) return '';
        if ($isformdata) $data = Base::newStripslashes($data);
        return var_export($data, TRUE);
    }

    /**
     * json字符串转换成array
     * @param $string
     * @return array|mixed
     */
    public static function json2array($string)
    {
        if (is_array($string)) {
            return $string;
        }
        try {
            $array = json_decode($string, true);
            return is_array($array) ? $array : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * array转换成功json字符串
     * @param $array
     * @param int $options
     * @return string
     */
    public static function array2json($array, $options = 0)
    {
        if (!is_array($array)) {
            return $array;
        }
        try {
            return json_encode($array, $options);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 叠加数组或对象
     * @param object|array $array
     * @param array $over
     * @return object|array
     */
    public static function array_over(&$array, $over = [])
    {
        if (is_array($over)) {
            foreach ($over as $key => $val) {
                if (is_array($array)) {
                    $array[$key] = $val;
                }
                if (is_object($array)) {
                    $array->$key = $val;
                }
            }
        }
        return $array;
    }

    /**
     * 获取数组第一个值
     * @param $array
     * @return mixed
     */
    public static function arrayFirst($array)
    {
        $val = '';
        if (is_array($array)) {
            foreach ($array as $item) {
                $val = $item;
                break;
            }
        }
        return $val;
    }

    /**
     * 获取数组最后一个值
     * @param $array
     * @return mixed
     */
    public static function arrayLast($array)
    {
        $val = '';
        if (is_array($array)) {
            foreach (array_reverse($array) as $item) {
                $val = $item;
                break;
            }
        }
        return $val;
    }

    /**
     * array转xml
     * @param $data
     * @param string $root 根节点
     * @return string
     */
    public static function array2xml($data, $root = '<xml>')
    {
        $str = "";
        if ($root) $str .= $root;
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $child = self::array2xml($val, false);
                $str .= "<$key>$child</$key>";
            } else {
                $str .= "<$key><![CDATA[$val]]></$key>";
            }
        }
        if ($root) $str .= '</xml>';
        return $str;
    }

    /**
     * xml转json
     * @param string $source 传的是文件，还是xml的string的判断
     * @return string
     */
    public static function xml2json($source)
    {
        if (is_file($source)) {
            $xml_array = @simplexml_load_file($source);
        } else {
            $xml_array = @simplexml_load_string($source, NULL, LIBXML_NOCDATA);
        }
        return json_encode($xml_array);
    }

    /**
     * 返回经stripslashes处理过的字符串或数组
     * @param array|string $string 需要处理的字符串或数组
     * @return array|int|string
     */
    public static function newStripslashes($string)
    {
        if (is_numeric($string)) {
            return $string;
        } elseif (!is_array($string)) {
            return stripslashes($string);
        }
        foreach ($string as $key => $val) $string[$key] = Base::newStripslashes($val);
        return $string;
    }

    /**
     * 返回经addslashes处理过的字符串或数组
     * @param array|string $string 需要处理的字符串或数组
     * @return array|int|string
     */
    public static function newAddslashes($string)
    {
        if (is_numeric($string)) {
            return $string;
        } elseif (!is_array($string)) {
            return addslashes($string);
        }
        foreach ($string as $key => $val) $string[$key] = Base::newAddslashes($val);
        return $string;
    }

    /**
     * 返回经trim处理过的字符串或数组
     * @param $string
     * @return array|string
     */
    public static function newTrim($string)
    {
        if (!is_array($string)) return trim($string);
        foreach ($string as $key => $val) $string[$key] = Base::newTrim($val);
        return $string;
    }

    /**
     * 返回经intval处理过的字符串或数组
     * @param $string
     * @return array|int
     */
    public static function newIntval($string)
    {
        if (!is_array($string)) return intval($string);
        foreach ($string as $key => $val) $string[$key] = Base::newIntval($val);
        return $string;
    }

    /**
     * 递归处理数组
     *
     * @param string $callback 如：'intval'、'trim'、'addslashes'、'stripslashes'、'htmlspecialchars'
     * @param array $array
     * @return array
     */
    public static function newArrayRecursive($callback, $array)
    {
        $func = function ($item) use (&$func, &$callback) {
            return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
        };

        return array_map($func, $array);
    }

    /**
     * 重MD5加密
     * @param $text
     * @param string $pass
     * @return string
     */
    public static function md52($text, $pass = '')
    {
        $_text = md5($text) . $pass;
        return md5($_text);
    }

    /**
     * 随机字符串
     * @param int $length 随机字符长度
     * @param string $type
     * @return string 1数字、2大小写字母、21小写字母、22大写字母、默认全部;
     */
    public static function generatePassword($length = 8, $type = '')
    {
        // 密码字符集，可任意添加你需要的字符
        switch ($type) {
            case '1':
                $chars = '0123456789';
                break;
            case '2':
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case '21':
                $chars = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case '22':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            default:
                $chars = $type ?: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                break;
        }
        $passwordstr = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $passwordstr .= $chars[mt_rand(0, $max)];
        }
        return $passwordstr;
    }

    /**
     * 同 generate_password 默认获取纯数字
     * @param $length
     * @param string $chars
     * @return string
     */
    public static function strRandom($length, $chars = '0123456789')
    {
        return Base::generatePassword($length, $chars);
    }

    /**
     * 判断两个地址域名是否相同
     * @param string $var1
     * @param string|array $var2
     * @return bool
     */
    public static function hostContrast($var1, $var2)
    {
        $arr1 = parse_url($var1);
        $host1 = $arr1['host'] ?? $var1;
        //
        $host2 = [];
        foreach (is_array($var2) ? $var2 : [$var2] as $url) {
            $arr2 = parse_url($url);
            $host2[] = $arr2['host'] ?? $url;
        }
        return in_array($host1, $host2);
    }

    /**
     * 获取url域名
     * @param string $var
     * @return mixed
     */
    public static function getHost($var = '')
    {
        if (empty($var)) {
            $var = url("/");
        }
        $arr = parse_url($var);
        return $arr['host'];
    }

    /**
     * 相对路径补全
     * @param string|array $str
     * @return string|array
     */
    public static function fillUrl($str = '')
    {
        global $_A;
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                $str[$key] = Base::fillUrl($item);
            }
            return $str;
        }
        if (empty($str)) {
            return $str;
        }
        if (str_starts_with($str, "//") ||
            str_starts_with($str, "http://") ||
            str_starts_with($str, "https://") ||
            str_starts_with($str, "ftp://") ||
            str_starts_with($str, "/") ||
            str_starts_with(str_replace(' ', '', $str), "data:image/")
        ) {
            return $str;
        } else {
            if ($_A['__fill_url_remote_url'] === true) {
                return "{{RemoteURL}}" . $str;
            }
            try {
                return url($str);
            } catch (\Throwable) {
                return self::getSchemeAndHost() . "/" . $str;
            }
        }
    }

    /**
     * 反 fillUrl
     * @param string $str
     * @return array|string
     */
    public static function unFillUrl($str = '')
    {
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                $str[$key] = Base::unFillUrl($item);
            }
            return $str;
        }
        try {
            $find = url('');
        } catch (\Throwable) {
            $find = self::getSchemeAndHost();
        }
        return Base::leftDelete($str, $find . '/');
    }

    /**
     * 获取主地址
     * @return string   如：http://127.0.0.1:8080
     */
    public static function getSchemeAndHost()
    {
        $scheme = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        return $scheme.($_SERVER['HTTP_HOST'] ?? '');
    }

    /**
     * 地址后拼接参数
     * @param $url
     * @param $parames
     * @return mixed|string
     */
    public static function urlAddparameter($url, $parames)
    {
        if ($parames && is_array($parames)) {
            $array = [];
            foreach ($parames as $key => $val) {
                $array[] = $key . "=" . $val;
            }
            if ($array) {
                $query = implode("&", $array);
                if (str_contains($url, "?")) {
                    $url .= "&" . $query;
                } else {
                    $url .= "?" . $query;
                }
            }
        }
        return $url;
    }

    /**
     * 格式化内容图片地址
     * @param $content
     * @return mixed
     */
    public static function formatContentImg($content)
    {
        $pattern = '/<img(.*?)src=("|\')(.*?)\2/is';
        if (preg_match($pattern, $content)) {
            preg_match_all($pattern, $content, $matchs);
            foreach ($matchs[3] as $index => $value) {
                if (!(str_starts_with($value, "http://") ||
                    str_starts_with($value, "https://") ||
                    str_starts_with($value, "ftp://") ||
                    str_starts_with(str_replace(' ', '', $value), "data:image/")
                )) {
                    if (str_starts_with($value, "//")) {
                        $value = "http:" . $value;
                    } elseif (str_starts_with($value, "/")) {
                        $value = substr($value, 1);
                    }
                    $newValue = "<img" . $matchs[1][$index] . "src=" . $matchs[2][$index] . self::fillUrl($value) . $matchs[2][$index];
                    $content = str_replace($matchs[0][$index], $newValue, $content);
                }
            }
        }
        return $content;
    }

    /**
     * 打散字符串，只留为数字的项
     * @param $delimiter
     * @param $string
     * @param bool $reInt 是否格式化值
     * @return array
     */
    public static function explodeInt($delimiter, $string = null, $reInt = true)
    {
        if ($string == null) {
            $string = $delimiter;
            $delimiter = ',';
        }
        $array = is_array($string) ? $string : explode($delimiter, $string);
        return self::arrayRetainInt($array, $reInt);
    }

    /**
     * 数组只保留数字的
     * @param $array
     * @param bool $reInt 是否格式化值
     * @return array
     */
    public static function arrayRetainInt($array, $reInt = false)
    {
        if (!is_array($array)) {
            return $array;
        }
        foreach ($array as $k => $v) {
            if (!is_numeric($v)) {
                unset($array[$k]);
            } elseif ($reInt === true) {
                $array[$k] = intval($v);
            }
        }
        return array_values($array);
    }

    /**
     * 数组拼接字符串（前后也加上）
     * @param $glue
     * @param $pieces
     * @param $around
     * @return string
     */
    public static function arrayImplode($glue = "", $pieces = null, $around = true)
    {
        if ($pieces == null) {
            $pieces = $glue;
            $glue = ',';
        }
        $pieces = array_values(array_filter(array_unique($pieces)));
        $string = implode($glue, $pieces);
        if ($around && $string) {
            $string = ",{$string},";
        }
        return $string;
    }

    /**
     * 判断是否二维数组
     * @param $array
     * @return bool
     */
    public static function isTwoArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $json = self::array2json($array);
        return (bool)self::leftExists($json, '[');
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
     * 检测手机号码格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isMobile($str)
    {
        if (preg_match("/^1([3456789])\d{9}$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测邮箱格式
     * @param $str
     * @return bool
     */
    public static function isEmail($str)
    {
        if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 正则判断是否纯数字
     * @param $str
     * @return bool
     */
    public static function isNumber($str)
    {
        if (preg_match("/^\d+$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 正则判断是否MAC地址
     * @param $str
     * @return bool
     */
    public static function isMac($str)
    {
        if (preg_match("/^[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断身份证是否正确
     * @param $id
     * @return bool
     */
    public static function isIdcard($id)
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if (!preg_match($regx, $id)) {
            return FALSE;
        }
        if (15 == strlen($id)) {
            //检查15位
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            //检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            //检查生日日期是否正确
            if (!strtotime($dtm_birth)) {
                return FALSE;
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b = (int)$id[$i];
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id, 17, 1)) {
                    return FALSE;
                } else {
                    return TRUE;
                }
            }
        }
    }

    /**
     * 阵列数组
     * @param $keys
     * @param $src
     * @param bool $default
     * @return array
     */
    public static function arrayElements($keys, $src, $default = FALSE)
    {
        $return = [];
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            if (isset($src[$key])) {
                $return[$key] = $src[$key];
            } else {
                $return[$key] = $default;
            }
        }
        return $return;
    }

    /**
     * 判断字符串存在(包含)
     * @param string $string
     * @param string $find
     * @return bool
     */
    public static function strExists($string, $find)
    {
        if (!is_string($string) || !is_string($find)) {
            return false;
        }
        return str_contains($string, $find);
    }

    /**
     * 判断字符串开头包含
     * @param string $string //原字符串
     * @param string $find //判断字符串
     * @param bool|false $lower //是否不区分大小写
     * @return bool
     */
    public static function leftExists($string, $find, $lower = false)
    {
        if (!is_string($string) || !is_string($find)) {
            return false;
        }
        if ($lower) {
            $string = strtolower($string);
            $find = strtolower($find);
        }
        return str_starts_with($string, $find);
    }

    /**
     * 判断字符串结尾包含
     * @param string $string //原字符串
     * @param string $find //判断字符串
     * @param bool|false $lower //是否不区分大小写
     * @return int
     */
    public static function rightExists($string, $find, $lower = false)
    {
        if (!is_string($string) || !is_string($find)) {
            return false;
        }
        if ($lower) {
            $string = strtolower($string);
            $find = strtolower($find);
        }
        return str_ends_with($string, $find);
    }

    /**
     * 删除开头指定字符串
     * @param $string
     * @param $find
     * @param bool $lower
     * @return string
     */
    public static function leftDelete($string, $find, $lower = false)
    {
        if (Base::leftExists($string, $find, $lower)) {
            $string = substr($string, strlen($find));
        }
        return $string ?: '';
    }

    /**
     * 删除结尾指定字符串
     * @param $string
     * @param $find
     * @param bool $lower
     * @return string
     */
    public static function rightDelete($string, $find, $lower = false)
    {
        if (Base::rightExists($string, $find, $lower)) {
            $string = substr($string, 0, strlen($find) * -1);
        }
        return $string;
    }

    /**
     * 替换开头指定字符串
     * @param $string
     * @param $find
     * @param $replace
     * @param $lower
     * @return mixed|string
     */
    public static function leftReplace($string, $find, $replace, $lower = false)
    {
        if (Base::leftExists($string, $find, $lower)) {
            $string = $replace . substr($string, strlen($find));
        }
        return $string;
    }

    /**
     * 替换结尾指定字符串
     * @param $string
     * @param $find
     * @param $replace
     * @param $lower
     * @return mixed|string
     */
    public static function rightReplace($string, $find, $replace, $lower = false)
    {
        if (Base::rightExists($string, $find, $lower)) {
            $string = substr($string, 0, strlen($find) * -1) . $replace;
        }
        return $string;
    }

    /**
     * 截取指定字符串
     * @param $str
     * @param string $ta
     * @param string $tb
     * @return string
     */
    public static function getMiddle($str, $ta = '', $tb = '')
    {
        if ($ta && str_contains($str, $ta)) {
            $str = substr($str, strpos($str, $ta) + strlen($ta));
        }
        if ($tb && str_contains($str, $tb)) {
            $str = substr($str, 0, strpos($str, $tb));
        }
        return $str;
    }

    /**
     * 自定义替换次数
     * @param $search
     * @param $replace
     * @param $subject
     * @param int $limit
     * @return string|string[]|null
     */
    public static function strReplaceLimit($search, $replace, $subject, $limit = -1)
    {
        if (is_array($search)) {
            foreach ($search as $k => $v) {
                $search[$k] = '`' . preg_quote($v, '`') . '`';
            }
        } else {
            $search = '`' . preg_quote($search, '`') . '`';
        }
        return preg_replace($search, $replace, $subject, $limit);
    }

    /**
     * 获取或设置
     * @param $setname          // 配置名称
     * @param bool $array       // 保存内容
     * @param false $isUpdate   // 保存内容为更新模式，默认否
     * @return array
     */
    public static function setting($setname, $array = false, $isUpdate = false)
    {
        global $_A;
        if (empty($setname)) {
            return [];
        }
        if ($array === false && isset($_A["__static_setting_" . $setname])) {
            return $_A["__static_setting_" . $setname];
        }
        $setting = [];
        $row = Setting::whereName($setname)->first();
        if ($row) {
            $setting = Base::string2array($row->setting);
        } else {
            $row = Setting::createInstance(['name' => $setname]);
            $row->save();
        }
        if ($array !== false) {
            if ($isUpdate && is_array($array)) {
                $setting = array_merge($setting, $array);
            } else {
                $setting = $array;
            }
            $row->updateInstance(['setting' => $setting]);
            $row->save();
        }
        $_A["__static_setting_" . $setname] = $setting;
        return $setting;
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
     * 获取设置值
     * @param $setname
     * @param $keyname
     * @param $defaultVal
     * @return mixed
     */
    public static function settingFind($setname, $keyname, $defaultVal = '')
    {
        $array = Base::setting($setname);
        return $array[$keyname] ?? $defaultVal;
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
        return str_replace("七", "日", Base::chinaNum($num));
    }

    /**
     * 获取(时间戳转)今天是星期几，只返回（几）
     * @param string|number $unixTime
     * @return string
     */
    public static function getTimeWeek($unixTime = '')
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
    public static function getTimeDayeSegment($unixTime = '')
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
     * JSON返回
     * @param $param
     * @return string
     */
    public static function jsonEcho($param)
    {
        $json = json_encode($param);
        $callback = Request::input('callback');
        if ($callback) {
            return $callback . '(' . $json . ')';
        } else {
            return $json;
        }
    }

    /**
     * 数组返回 正确
     * @param $msg
     * @param string|array $data
     * @param int $ret
     * @return array
     */
    public static function retSuccess($msg, $data = [], $ret = 1)
    {
        return [
            'ret' => $ret,
            'msg' => Doo::translate($msg),
            'data' => $data
        ];
    }

    /**
     * 数组返回 错误
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return array
     */
    public static function retError($msg, $data = [], $ret = 0)
    {
        return [
            'ret' => $ret,
            'msg' => Doo::translate($msg),
            'data' => $data
        ];
    }

    /**
     * Ajax 错误返回
     * @param $msg
     * @param array $data
     * @param int $ret
     * @param int $abortCode
     * @return array
     */
    public static function ajaxError($msg, $data = [], $ret = 0, $abortCode = 404)
    {
        if (Request::header('Content-Type') === 'application/json') {
            return Base::retError($msg, $data, $ret);
        } else {
            abort($abortCode, $msg);
        }
    }

    /**
     * JSON返回 正确
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return string
     */
    public static function jsonSuccess($msg, $data = [], $ret = 1)
    {
        return Base::jsonEcho(Base::retSuccess($msg, $data, $ret));
    }

    /**
     * JSON返回 错误
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return string
     */
    public static function jsonError($msg, $data = [], $ret = 0)
    {
        return Base::jsonEcho(Base::retError($msg, $data, $ret));
    }

    /**
     * 是否错误
     * @param $param
     * @return bool
     */
    public static function isError($param)
    {
        return !isset($param['ret']) || intval($param['ret']) <= 0;
    }

    /**
     * 是否正确
     * @param $param
     * @return bool
     */
    public static function isSuccess($param)
    {
        return !self::isError($param);
    }

    /**
     * 获取数组的第几个值
     * @param $arr
     * @param int $i
     * @return array
     */
    public static function getArray($arr, $i = 1)
    {
        $array = [];
        $j = 1;
        foreach ($arr as $item) {
            $array[] = $item;
            if ($i >= $j) {
                break;
            }
            $j++;
        }
        return $array;
    }

    /**
     * 小时转天/小时
     * @param $hour
     * @return string
     */
    public static function forumHourDay($hour)
    {
        $hour = intval($hour);
        if ($hour > 24) {
            $day = floor($hour / 24);
            $hour -= $day * 24;
            return $day . '天' . $hour . '小时';
        }
        return $hour . '小时';
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
     * 创建Carbon对象
     * @param $var
     * @return Carbon
     */
    public static function newCarbon($var)
    {
        if (self::isNumber($var)) {
            if (preg_match("/^\d{13,}$/", $var)) {
                $var = $var / 1000;
            }
            return Carbon::createFromTimestamp($var);
        } elseif (is_string($var)) {
            return Carbon::parse(trim($var));
        } else {
            return Carbon::now();
        }
    }

    /**
     * 用户名、邮箱、手机帐号、银行卡号中间字符串以*隐藏
     * @param $str
     * @return string
     */
    public static function formatName($str)
    {
        if (str_contains($str, "@")) {
            $email_array = explode("@", $str);
            $prevfix = substr($str, 0, strlen($email_array[0]) < 4 ? 1 : 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
            return $prevfix . $str;
        }
        if (Base::isMobile($str)) {
            return substr($str, 0, 3) . "****" . substr($str, -4);
        }
        $pattern = '/([\d]{4})([\d]{4})([\d]{4})([\d]{4})([\d]*)?/i';
        if (preg_match($pattern, $str)) {
            return preg_replace($pattern, '$1 **** **** **** $5', $str);
        }
        $pattern = '/([\d]{4})([\d]{4})([\d]{4})([\d]*)?/i';
        if (preg_match($pattern, $str)) {
            return preg_replace($pattern, '$1 **** **** $4', $str);
        }
        $pattern = '/([\d]{4})([\d]{4})([\d]*)?/i';
        if (preg_match($pattern, $str)) {
            return preg_replace($pattern, '$1 **** $3', $str);
        }
        return substr($str, 0, 3) . "***" . substr($str, -1);
    }

    /**
     * 数字每4位加一空格
     * @param $str
     * @param string $interval
     * @return string
     */
    public static function fourFormat($str, $interval = " ")
    {
        if (!is_numeric($str)) return $str;
        //
        $text = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $text .= $str[$i];
            if ($i % 4 == 3) {
                $text .= $interval;
            }
        }
        return $text;
    }

    /**
     * 保留两位小数点
     * @param $str
     * @param bool $float
     * @return float
     */
    public static function twoFloat($str, $float = false)
    {
        $str = sprintf("%.2f", floatval($str));
        if ($float === true) {
            $str = floatval($str);
        }
        return $str;
    }

    /**
     * 获取时间戳
     * @param bool $refresh
     * @return int
     */
    public static function time($refresh = false)
    {
        global $_A;
        if (!isset($_A["__static_time"]) || $refresh === true) {
            $_A["__static_time"] = time();
        }
        return $_A["__static_time"];
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
     * 取ip前3段
     * @param $ip
     * @return mixed|string
     */
    public static function getIp3Pre($ip)
    {
        preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3})\.\d{1,3}/", $ip, $match);
        if ($match) {
            return $match[1];
        } else {
            return "";
        }
    }

    /**
     * 获取IP地址
     * @return string
     */
    public static function getIp()
    {
        global $_A;
        if (!isset($_A["__static_ip"])) {
            if (getenv('HTTP_CLIENT_IP') and strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
                $onlineip = getenv('HTTP_CLIENT_IP');
            } elseif (isset($_SERVER['HTTP_CLIENT_IP']) and $_SERVER['HTTP_CLIENT_IP'] and strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')) {
                $onlineip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (getenv('HTTP_X_FORWARDED_FOR') and strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
                $onlineip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and $_SERVER['HTTP_X_FORWARDED_FOR'] and strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')) {
                $onlineip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (getenv('REMOTE_ADDR') and strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
                $onlineip = getenv('REMOTE_ADDR');
            } elseif (isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] and strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $onlineip = $_SERVER['REMOTE_ADDR'];
            } elseif (Request::header('X-Real-IP')) {
                $onlineip = Request::header('X-Real-IP');
            } else {
                $onlineip = '0,0,0,0';
            }
            preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $onlineip, $match);
            $_A["__static_ip"] = $match ? ($match[0] ?: 'unknown') : '';
        }
        return $_A["__static_ip"];
    }

    /**
     * 是否是中国IP：-1错误、1是、0否
     * @param string $ip
     * @return int
     */
    public static function isCnIp($ip = '')
    {
        if (empty($ip)) {
            $ip = self::getIp();
        }
        $cacheKey = "isCnIp::" . md5($ip);
        //
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($ip) {
            $file = dirname(__FILE__) . '/IpAddr/all_cn.txt';
            if (!file_exists($file)) {
                return -1;
            }
            $in = false;
            $myFile = fopen($file, "r");
            $i = 0;
            while (!feof($myFile)) {
                $i++;
                $cidr = trim(fgets($myFile));
                if (Base::ipInRange($ip, $cidr)) {
                    $in = true;
                    break;
                }
            }
            fclose($myFile);
            return $in ? 1 : 0;
        });
        if ($result === -1) {
            Cache::forget($cacheKey);
        }
        //
        return intval($result);
    }

    /**
     * 验证IP地址范围
     * $range 支持多种写法
     *  - Wildcard： 1.2.3.*
     *  - CIRD：1.2.3/24 或者 1.2.3.4/255.255.255.0
     *  - Start-End: 1.2.3.0-1.2.3.255
     * @param $ip
     * @param $range
     * @return bool
     */
    public static function ipInRange($ip, $range)
    {
        if (substr_count($ip, '.') == 3 && $ip == $range) {
            return true;
        }
        if (str_contains($range, '/')) {
            list($range, $netmask) = explode('/', $range, 2);
            if (str_contains($netmask, '.')) {
                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);
                return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
            } else {
                $x = explode('.', $range);
                while (count($x) < 4) {
                    $x[] = '0';
                }
                list($a, $b, $c, $d) = $x;
                $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
                $range_dec = ip2long($range);
                $ip_dec = ip2long($ip);
                $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                $netmask_dec = ~$wildcard_dec;
                return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
            }
        } else {
            if (str_contains($range, '*')) {
                $lower = str_replace('*', '0', $range);
                $upper = str_replace('*', '255', $range);
                $range = "$lower-$upper";
            }
            if (str_contains($range, '-')) {
                list($lower, $upper) = explode('-', $range, 2);
                $lower_dec = (float)sprintf("%u", ip2long($lower));
                $upper_dec = (float)sprintf("%u", ip2long($upper));
                $ip_dec = (float)sprintf("%u", ip2long($ip));
                return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
            }
            return false;
        }
    }

    /**
     * 多维 array_values
     * @param $array
     * @param string $keyName
     * @param string $valName
     * @return array
     */
    public static function array_values_recursive($array, $keyName = 'key', $valName = 'item')
    {
        if (is_array($array) && count($array) > 0) {
            $temp = [];
            foreach ($array as $key => $value) {
                $continue = false;
                if (is_array($value) && count($value) > 0) {
                    $continue = true;
                    foreach ($value as $item) {
                        if (!is_array($item)) {
                            $continue = false;
                            break;
                        }
                    }
                }
                $temp[] = [
                    $keyName => $key,
                    $valName => $continue ? self::array_values_recursive($value, $keyName, $valName) : $value,
                ];
            }
            return $temp;
        }
        return $array;
    }

    /**
     * 多维数组只保留指定键值
     * @param $array
     * @param $keys
     * @return array
     */
    public static function array_only_recursive($array, $keys) {
        return array_map(function ($item) use ($keys) {
            return is_array($item)
                ? array_intersect_key($item, array_flip($keys))
                : $item;
        }, $array);
    }

    /**
     * 是否微信
     * @return bool
     */
    public static function isWechat()
    {
        return str_contains(Request::server('HTTP_USER_AGENT'), 'MicroMessenger');
    }

    /**
     * 获取浏览器类型
     * @return string
     */
    public static function browser()
    {
        $user_agent = Request::server('HTTP_USER_AGENT');
        if (str_contains($user_agent, 'AlipayClient')) {
            return 'alipay';
        } elseif (str_contains($user_agent, 'MicroMessenger')) {
            return 'weixin';
        } else {
            return 'none';
        }
    }

    /**
     * 获取平台类型
     * @return string
     */
    public static function platform()
    {
        $platform = strtolower(trim(Request::header('platform')));
        if (in_array($platform, ['android', 'ios', 'win', 'mac', 'web'])) {
            return $platform;
        }
        $agent = strtolower(Request::server('HTTP_USER_AGENT'));
        if (str_contains($agent, 'android')) {
            $platform = 'android';
        } elseif (str_contains($agent, 'iphone') || str_contains($agent, 'ipad')) {
            $platform = 'ios';
        } else {
            $platform = 'unknown';
        }
        return $platform;
    }

    /**
     * 是否是App移动端
     * @return bool
     */
    public static function isEEUIApp()
    {
        $userAgent = strtolower(Request::server('HTTP_USER_AGENT'));
        return str_contains($userAgent, 'kuaifan_eeui');
    }

    /**
     * 返回根据距离sql排序语句
     * @param $lat
     * @param $lng
     * @param string $latName
     * @param string $lngName
     * @return string
     */
    public static function acos($lat, $lng, $latName = 'lat', $lngName = 'lng')
    {
        $lat = floatval($lat);
        $lng = floatval($lng);
        return 'ACOS(
		SIN((' . $lat . ' * 3.1415) / 180) * SIN((' . $latName . ' * 3.1415) / 180) + COS((' . $lat . ' * 3.1415) / 180) * COS((' . $latName . ' * 3.1415) / 180) * COS(
			(' . $lng . ' * 3.1415) / 180 - (' . $lngName . ' * 3.1415) / 180
		)
	) * 6380';
    }

    /**
     * 获取每页数量
     * @param $max
     * @param $default
     * @param string $inputName
     * @return mixed
     */
    public static function getPaginate($max, $default, $inputName = 'pagesize')
    {
        return Min(Max(Base::nullShow(Request::input($inputName), $default), 1), $max);
    }

    /**
     * base64语音保存
     * @param array $param [ base64=带前缀的base64, path=>文件路径 ]
     * @return array [name=>文件名, size=>文件大小(单位KB),file=>绝对地址, path=>相对地址, url=>全路径地址, ext=>文件后缀名]
     */
    public static function record64save($param)
    {
        $base64 = $param['base64'];
        if (preg_match('/^(data:\s*audio\/(\w+);base64,)/', $base64, $res)) {
            $extension = $res[2];
            if (!in_array($extension, ['mp3', 'wav'])) {
                return Base::retError('语音格式错误');
            }
            $fileName = 'record_' . md5($base64) . '.' . $extension;
            $fileDir = $param['path'];
            $filePath = public_path($fileDir);
            Base::makeDir($filePath);
            if (file_put_contents($filePath . $fileName, base64_decode(str_replace($res[1], '', $base64)))) {
                $fileSize = filesize($filePath . $fileName);
                $array = [
                    "name" => $fileName,                                                //原文件名
                    "size" => Base::twoFloat($fileSize / 1024, true),         //大小KB
                    "file" => $filePath . $fileName,                                    //文件的完整路径                "D:\www....KzZ.jpg"
                    "path" => $fileDir . $fileName,                                     //相对路径                     "uploads/pic....KzZ.jpg"
                    "url" => Base::fillUrl($fileDir . $fileName),                   //完整的URL                    "https://.....hhsKzZ.jpg"
                    "ext" => $extension,                                                //文件后缀名
                ];
                return Base::retSuccess('success', $array);
            }
        }
        return Base::retError('语音保存失败');
    }

    /**
     * image64图片保存
     * @param array $param [ image64=带前缀的base64, path=>文件路径, fileName=>文件名称, scale=>[压缩原图宽,高, 压缩方式], autoThumb=>false不要自动生成缩略图, 'quality'=>压缩图片质量(默认：0不压缩) ]
     * @return array [name=>文件名, size=>文件大小(单位KB),file=>绝对地址, path=>相对地址, url=>全路径地址, ext=>文件后缀名]
     */
    public static function image64save($param)
    {
        $imgBase64 = $param['image64'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgBase64, $res)) {
            $extension = $res[2];
            if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                return Base::retError('图片格式错误');
            }
            $scaleName = "";
            if ($param['fileName']) {
                $fileName = basename($param['fileName']);
            } else {
                if ($param['scale'] && is_array($param['scale'])) {
                    list($width, $height) = $param['scale'];
                    if ($width > 0 || $height > 0) {
                        $scaleName = "_{WIDTH}x{HEIGHT}";
                        if (isset($param['scale'][2])) {
                            $scaleName .= "_c{$param['scale'][2]}";
                        }
                    }
                }
                $fileName = 'paste_' . md5($imgBase64) . '.' . $extension;
                $scaleName = md5_file($imgBase64) . $scaleName . '.' . $extension;
            }
            $fileDir = $param['path'];
            $filePath = public_path($fileDir);
            Base::makeDir($filePath);
            if (file_put_contents($filePath . $fileName, base64_decode(str_replace($res[1], '', $imgBase64)))) {
                $fileSize = filesize($filePath . $fileName);
                $array = [
                    "name" => $fileName,                                                //原文件名
                    "size" => Base::twoFloat($fileSize / 1024, true),         //大小KB
                    "file" => $filePath . $fileName,                                    //文件的完整路径                "D:\www....KzZ.jpg"
                    "path" => $fileDir . $fileName,                                     //相对路径                     "uploads/pic....KzZ.jpg"
                    "url" => Base::fillUrl($fileDir . $fileName),                   //完整的URL                    "https://.....hhsKzZ.jpg"
                    "thumb" => '',                                                      //缩略图（预览图）               "https://.....hhsKzZ.jpg_thumb.jpg"
                    "width" => -1,                                                      //图片宽度
                    "height" => -1,                                                     //图片高度
                    "ext" => $extension,                                                //文件后缀名
                ];
                // 图片尺寸
                $paramet = getimagesize($array['file']);
                $array['width'] = $paramet[0];
                $array['height'] = $paramet[1];
                // 原图裁剪
                if ($param['scale'] && is_array($param['scale'])) {
                    list($width, $height) = $param['scale'];
                    if (($width > 0 && $array['width'] > $width) || ($height > 0 && $array['height'] > $height)) {
                        // 图片裁剪
                        $cutMode = ($width > 0 && $height > 0) ? 'cover' : 'percentage';
                        $cutMode = $param['scale'][2] ?? $cutMode;
                        Image::thumbImage($array['file'], $array['file'], $width, $height, 90, $cutMode);
                        // 更新图片尺寸
                        $paramet = getimagesize($array['file']);
                        $array['width'] = $paramet[0];
                        $array['height'] = $paramet[1];
                        // 重命名
                        if ($scaleName) {
                            $scaleName = str_replace(['{WIDTH}', '{HEIGHT}'], [$array['width'], $array['height']], $scaleName);
                            if (rename($array['file'], Base::rightDelete($array['file'], $fileName) . $scaleName)) {
                                $array['file'] = Base::rightDelete($array['file'], $fileName) . $scaleName;
                                $array['path'] = Base::rightDelete($array['path'], $fileName) . $scaleName;
                                $array['url'] = Base::rightDelete($array['url'], $fileName) . $scaleName;
                            }
                        }
                    }
                }
                // 压缩图片
                $quality = intval($param['quality']);
                if ($quality > 0) {
                    Image::compressImage($array['file'], null, $quality);
                    $array['size'] = Base::twoFloat(filesize($array['file']) / 1024, true);
                }
                //生成缩略图
                $array['thumb'] = $array['path'];
                if ($extension === 'gif' && !isset($param['autoThumb'])) {
                    $param['autoThumb'] = false;
                }
                if ($param['autoThumb'] !== false) {
                    if ($extension = Image::thumbImage($array['file'], $array['file'] . "_thumb.{*}", 320, 0, 80)) {
                        $array['thumb'] .= "_thumb.{$extension}";
                    }
                }
                $array['thumb'] = Base::fillUrl($array['thumb']);
                return Base::retSuccess('success', $array);
            }
        }
        return Base::retError('图片保存失败');
    }

    /**
     * 上传文件
     * @param array $param [
        type=[文件类型],
        file=>Request::file,
        path=>文件路径,
        fileName=>文件名称,
        scale=>[压缩原图宽,高, 压缩方式],
        size=>限制大小KB,
        autoThumb=>false不要自动生成缩略图,
        chmod=>权限(默认0644),
        quality=>压缩图片质量(默认：0不压缩),
        convertVideo=>转换视频格式(默认false) ,
     ]
     * @return array [
        name=>原文件名,
        size=>文件大小(单位KB),
        file=>绝对地址,
        path=>相对地址,
        url=>全路径地址,
        ext=>文件后缀名,
     ]
     */
    public static function upload($param)
    {
        $file = $param['file'];
        $chmod = $param['chmod'] ?: 0644;
        if (empty($file)) {
            return Base::retError("您没有选择要上传的文件");
        }
        if ($file->isValid()) {
            Base::makeDir(public_path($param['path']));
            //
            switch ($param['type']) {
                case 'png':
                    $type = ['png'];
                    break;
                case 'image':
                    $type = ['jpg', 'jpeg', 'webp', 'gif', 'png'];
                    break;
                case 'video':
                    $type = ['rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4', 'mov', 'webm'];
                    break;
                case 'audio':
                    $type = ['mp3', 'wma', 'wav', 'amr'];
                    break;
                case 'excel':
                    $type = ['xls', 'xlsx'];
                    break;
                case 'app':
                    $type = ['apk'];
                    break;
                case 'zip':
                    $type = ['zip'];
                    break;
                case 'file':
                    $type = ['jpg', 'jpeg', 'webp', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mp3', 'mp4', 'mov', 'webm', 'pr', 'psd', 'svg', 'tif'];
                    break;
                case 'firmware':
                    $type = ['img', 'tar', 'bin'];
                    break;
                case 'md':
                    $type = ['md'];
                    break;
                case 'publish':
                    $type = ['yml', 'yaml', 'dmg', 'pkg', 'blockmap', 'zip', 'exe', 'msi', 'apk'];
                    break;
                case 'more':
                    $type = []; // 不限制上传文件类型
                    break;
                default:
                    return Base::retError('错误的类型参数');
            }
            $extension = strtolower($file->getClientOriginalExtension());
            if ($type && !in_array($extension, $type)) {
                return Base::retError('文件格式错误，限制类型：' . implode(",", $type));
            }
            $limitSize = intval($param['size']);
            if ($limitSize <= 0) {
                $fileUploadLimit = intval(Base::settingFind('system', 'file_upload_limit', 0));
                $limitSize = $fileUploadLimit * 1024;
            }
            try {
                $fileSize = $file->getSize();
                if ($limitSize > 0 && $fileSize > $limitSize * 1024) {
                    return Base::retError('文件大小超限，最大限制：' . $limitSize . 'KB');
                }
            } catch (\Throwable) {
                $fileSize = 0;
            }
            $scaleName = "";
            if ($param['fileName'] === true) {
                $fileName = $file->getClientOriginalName();
            } elseif ($param['fileName']) {
                $fileName = basename($param['fileName']);
            } else {
                if ($param['scale'] && is_array($param['scale'])) {
                    list($width, $height) = $param['scale'];
                    if ($width > 0 || $height > 0) {
                        $scaleName = "_{WIDTH}x{HEIGHT}";
                        if (isset($param['scale'][2])) {
                            $scaleName .= "_c{$param['scale'][2]}";
                        }
                    }
                }
                $fileName = md5_file($file);
                $scaleName = md5_file($file) . $scaleName;
                if ($extension) {
                    $fileName = $fileName . '.' . $extension;
                    $scaleName = $scaleName . '.' . $extension;
                }
            }
            //
            $file->move(public_path($param['path']), $fileName);
            //
            $path = $param['path'] . $fileName;
            $array = [
                "name" => $file->getClientOriginalName(),                       //原文件名
                "size" => Base::twoFloat($fileSize / 1024, true),     //大小KB
                "file" => public_path($path),                                   //文件的完整路径                "D:\www....KzZ.jpg"
                "path" => $path,                                                //相对路径                     "uploads/pic....KzZ.jpg"
                "url" => Base::fillUrl($path),                                  //完整的URL                    "https://.....hhsKzZ.jpg"
                "thumb" => '',                                                  //缩略图（预览图）               "https://.....hhsKzZ.jpg_thumb.jpg"
                "width" => -1,                                                  //图片宽度
                "height" => -1,                                                 //图片高度
                "ext" => $extension,                                            //文件后缀名
            ];
            if (!is_file($array['file'])) {
                return Base::retError('上传失败');
            }
            @chmod($array['file'], $chmod);
            // iOS照片颠倒处理
            if (in_array($array['ext'], ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                $data = imagecreatefromstring(file_get_contents($array['file']));
                $exif = @exif_read_data($array['file']);
                if (!empty($exif['Orientation'])) {
                    $data = match ($exif['Orientation']) {
                        8 => imagerotate($data, 90, 0),
                        3 => imagerotate($data, 180, 0),
                        6 => imagerotate($data, -90, 0),
                        default => null,
                    };
                    if ($data !== null) {
                        imagejpeg($data, $array['file']);
                    }
                }
            }
            //
            if ($param['convertVideo'] && in_array($array['ext'], ['mov', 'webm'])) {
                // 转换视频格式
                $output = Base::rightReplace($array['file'], ".{$array['ext']}", '.mp4');
                if ($array['ext'] === 'webm') {
                    $command = sprintf("ffmpeg -y -i %s -strict experimental %s 2>&1", escapeshellarg($array['file']), escapeshellarg($output));
                } else {
                    $command = sprintf("ffmpeg -y -i %s -c:v copy -c:a copy %s 2>&1", escapeshellarg($array['file']), escapeshellarg($output));
                }
                @shell_exec($command);
                if (file_exists($output) && filesize($output) > 0) {
                    @unlink($array['file']);
                    $array = array_merge($array, [
                        "name" => Base::rightReplace($array['name'], ".{$array['ext']}", '.mp4'),
                        "size" => Base::twoFloat(filesize($output) / 1024, true),
                        "file" => $output,
                        "path" => Base::rightReplace($array['path'], ".{$array['ext']}", '.mp4'),
                        "url" => Base::rightReplace($array['url'], ".{$array['ext']}", '.mp4'),
                        "ext" => 'mp4',
                    ]);
                }
            }
            if (in_array($array['ext'], ['mov', 'webm', 'mp4'])) {
                // 视频尺寸
                $thumbFile = $array['file'] . '_thumb.jpg';
                $command = sprintf("ffmpeg -y -i %s -ss 1 -vframes 1 %s 2>&1", escapeshellarg($array['file']), escapeshellarg($thumbFile));
                @shell_exec($command);
                if (file_exists($thumbFile) && filesize($thumbFile) > 0) {
                    $paramet = getimagesize($thumbFile);
                    $array['width'] = $paramet[0];
                    $array['height'] = $paramet[1];
                    $array['thumb'] = $array['path'] . '_thumb.jpg';
                    Image::compressImage($thumbFile, null, 80);
                }
            }
            if (in_array($array['ext'], ['jpg', 'jpeg', 'webp', 'gif', 'png'])) {
                // 获取图片尺寸
                $paramet = getimagesize($array['file']);
                $array['width'] = $paramet[0];
                $array['height'] = $paramet[1];
                // 原图裁剪
                if ($param['scale'] && is_array($param['scale'])) {
                    list($width, $height) = $param['scale'];
                    if (($width > 0 && $array['width'] > $width) || ($height > 0 && $array['height'] > $height)) {
                        // 图片裁剪
                        $cutMode = ($width > 0 && $height > 0) ? 'cover' : 'percentage';
                        $cutMode = $param['scale'][2] ?? $cutMode;
                        Image::thumbImage($array['file'], $array['file'], $width, $height, 90, $cutMode);
                        // 更新图片尺寸
                        $paramet = getimagesize($array['file']);
                        $array['width'] = $paramet[0];
                        $array['height'] = $paramet[1];
                        // 重命名
                        if ($scaleName) {
                            $scaleName = str_replace(['{WIDTH}', '{HEIGHT}'], [$array['width'], $array['height']], $scaleName);
                            if (rename($array['file'], Base::rightDelete($array['file'], $fileName) . $scaleName)) {
                                $array['file'] = Base::rightDelete($array['file'], $fileName) . $scaleName;
                                $array['path'] = Base::rightDelete($array['path'], $fileName) . $scaleName;
                                $array['url'] = Base::rightDelete($array['url'], $fileName) . $scaleName;
                            }
                        }
                    }
                }
                // 压缩图片
                $quality = intval($param['quality']);
                if ($quality > 0) {
                    Image::compressImage($array['file'], null, $quality);
                    $array['size'] = Base::twoFloat(filesize($array['file']) / 1024, true);
                }
                // 生成缩略图
                $array['thumb'] = $array['path'];
                if ($array['ext'] === 'gif' && !isset($param['autoThumb'])) {
                    $param['autoThumb'] = false;
                }
                if ($param['autoThumb'] !== false) {
                    if ($array['ext'] = Image::thumbImage($array['file'], $array['file'] . "_thumb.{*}", 320, 0, 80)) {
                        $array['thumb'] .= "_thumb.{$array['ext']}";
                    }
                }
                $array['thumb'] = Base::fillUrl($array['thumb']);
            }
            //
            return Base::retSuccess('success', $array);
        } else {
            return Base::retError($file->getErrorMessage());
        }
    }

    /**
     * 上传文件移动
     * @param array $uploadResult
     * @param string $newPath "/" 结尾
     * @return array
     */
    public static function uploadMove($uploadResult, $newPath)
    {
        if (str_ends_with($newPath, "/") && file_exists($uploadResult['file'])) {
            Base::makeDir(public_path($newPath));
            $oldPath = dirname($uploadResult['path']) . "/";
            $newFile = str_replace($oldPath, $newPath, $uploadResult['file']);
            if (rename($uploadResult['file'], $newFile)) {
                $oldUrl = $uploadResult['url'];
                $uploadResult['file'] = $newFile;
                $uploadResult['path'] = str_replace($oldPath, $newPath, $uploadResult['path']);
                $uploadResult['url'] = str_replace($oldPath, $newPath, $uploadResult['url']);
                if ($uploadResult['thumb'] == $oldUrl) {
                    $uploadResult['thumb'] = $uploadResult['url'];
                } elseif ($uploadResult['thumb']) {
                    $oldThumb = substr($uploadResult['thumb'], strpos($uploadResult['thumb'], $newPath));
                    $newThumb = str_replace($oldPath, $newPath, $oldThumb);
                    if (file_exists(public_path($oldThumb)) && rename(public_path($oldThumb), public_path($newThumb))) {
                        $uploadResult['thumb'] = str_replace($oldPath, $newPath, $uploadResult['thumb']);
                    }
                }
            }
        }
        return $uploadResult;
    }

    /**
     * 是否缩略图
     * @param $file
     * @return bool
     */
    public static function isThumb($file): bool
    {
        return preg_match('/_thumb\.(png|jpg|jpeg)$/', $file);
    }

    /**
     * 获取缩略图后缀
     * @param $file
     * @return string
     */
    public static function getThumbExt($file): string
    {
        if (file_exists($file . '_thumb.png')) {
            return 'png';
        } elseif (file_exists($file . '_thumb.jpg')) {
            return 'jpg';
        } elseif (file_exists($file . '_thumb.jpeg')) {
            return 'jpeg';
        } else {
            return '';
        }
    }

    /**
     * 缩略图还原
     * @param $file
     * @return string
     */
    public static function thumbRestore($file): string
    {
        $file = preg_replace('/_thumb\.(png|jpg|jpeg)$/', '', $file);
        return preg_replace('/\/crop\/([^\/]+)$/', '', $file);
    }

    /**
     * 获取后缀名图标相对地址
     * @param $ext
     * @return string
     */
    public static function extIcon($ext)
    {
        if ($ext == "docx") {
            $ext = 'doc';
        } elseif ($ext == "xlsx") {
            $ext = 'xls';
        } elseif ($ext == "pptx") {
            $ext = 'ppt';
        }
        if (in_array($ext, ["ai", "avi", "bmp", "cdr", "doc", "eps", "gif", "mov", "mp3", "mp4", "pdf", "ppt", "pr", "psd", "rar", "svg", "tif", "txt", "xls", "zip"])) {
            return 'images/ext/' . $ext . '.png';
        } else {
            return 'images/ext/file.png';
        }
    }

    /**
     * 排列组合（无重复）
     * @param $arr
     * @param $m
     * @return array
     */
    public static function getCombinationToString($arr, $m)
    {
        $result = [];
        if ($m == 1) {
            return $arr;
        }

        if ($m == count($arr)) {
            $result[] = implode(',', $arr);
            return $result;
        }

        $temp_firstelement = $arr[0];
        unset($arr[0]);
        $arr = array_values($arr);
        $temp_list1 = self::getCombinationToString($arr, ($m - 1));

        foreach ($temp_list1 as $s) {
            $s = $temp_firstelement . ',' . $s;
            $result[] = $s;
        }
        unset($temp_list1);

        $temp_list2 = self::getCombinationToString($arr, $m);
        foreach ($temp_list2 as $s) {
            $result[] = $s;
        }
        unset($temp_list2);

        return $result;
    }

    /**
     * 不同元素交叉组合（多个数组）
     * @return array
     */
    public static function getNewArray()
    {
        $args = func_get_args();
        $pailie = function ($arr1, $arr2) {
            $arr = [];
            $k = 0;
            foreach ($arr1 as $k1 => $v1) {
                foreach ($arr2 as $k2 => $v2) {
                    $arr[$k] = $v1 . "," . $v2;
                    $k++;
                }
            }
            return $arr;
        };
        $arr = [];
        foreach ($args as $k => $v) {
            if (isset($args[$k + 1]) && $args[$k + 1]) {
                $arr[$k] = match ($k) {
                    0 => $pailie($v, $args[$k + 1]),
                    default => $pailie($arr[$k - 1], $args[$k + 1]),
                };
            }
        }
        $key = count($arr) - 1;
        return array_values($arr[$key]);
    }

    /**
     * 把返回的数据集转换成Tree
     * @param array $list 要转换的数据集
     * @param string $pk id标记字段
     * @param string $pid parent标记字段
     * @param string $child 生成子类字段
     * @param int $root
     * @return array
     */
    public static function list2Tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0)
    {
        if (!is_array($list)) {
            return [];
        }

        // 创建基于主键的数组引用
        $aRefer = [];
        foreach ($list as $key => $data) {
            $list[$key][$child] = [];
            $aRefer[$data[$pk]] = &$list[$key];
        }

        $tree = [];

        foreach ($list as &$data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root === $parentId) {
                $tree[] = &$data;
            } else {
                if (isset($aRefer[$parentId])) {
                    $parent = &$aRefer[$parentId];
                    $parent[$child][] = &$data;
                }
            }
        }

        return $tree;
    }

    /**
     * 路径拼接
     * @param ...$segments
     * @return string
     */
    public static function joinPath(...$segments)
    {
        if (count($segments) === 0) {
            return "";
        }
        $array = array_map(function ($segment) {
            return trim($segment, DIRECTORY_SEPARATOR);
        }, $segments);
        $prefix = str_starts_with($segments[0], DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : "";
        return $prefix . implode(DIRECTORY_SEPARATOR, $array);
    }

    /**
     * 递归获取所有文件
     * @param $dir
     * @param bool|int $recursive
     * @return array
     */
    public static function recursiveFiles($dir, $recursive = true)
    {
        if ($recursive && is_numeric($recursive)) {
            $recursive--;
        }
        $array = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item != '..' && $item != '.') {
                $itemPath = self::joinPath($dir, $item);
                if (is_dir($itemPath)) {
                    if ($recursive) {
                        $array = array_merge($array, self::recursiveFiles($itemPath, $recursive));
                    }
                } else {
                    $array[] = $itemPath;
                }
            }
        }
        return $array;
    }

    /**
     * 递归获取所有目录
     * @param $dir
     * @param bool|int $recursive
     * @return array
     */
    public static function recursiveDirs($dir, $recursive = true)
    {
        if ($recursive && is_numeric($recursive)) {
            $recursive--;
        }
        $array = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item != '..' && $item != '.') {
                $itemPath = self::joinPath($dir, $item);
                if (is_dir($itemPath)) {
                    $array[] = $itemPath;
                    if ($recursive) {
                        $array = array_merge($array, self::recursiveDirs($itemPath, $recursive));
                    }
                }
            }
        }
        return $array;
    }

    /**
     * 获取中文字符拼音首字母
     * @param $str
     * @return string
     */
    public static function getFirstCharter($str)
    {
        if (empty($str)) {
            return '';
        }
        $first = mb_substr($str, 0, 1);
        if (preg_match("/^\d$/", $first)) {
            return '#';
        }
        if (!preg_match("/^[a-zA-Z]$/", $first)) {
            $pinyin = new Pinyin();
            $first = $pinyin->abbr($first, '', PINYIN_NAME);
        }
        return $first ? strtoupper($first) : '#';
    }

    /**
     * 中文转拼音
     * @param $str
     * @return string
     */
    public static function cn2pinyin($str)
    {
        if (empty($str)) {
            return '';
        }
        if (!preg_match("/^[a-zA-Z0-9_.]+$/", $str)) {
            $str = Cache::rememberForever("cn2pinyin:" . md5($str), function() use ($str) {
                $pinyin = new Pinyin();
                return $pinyin->permalink($str, '');
            });
        }
        return $str;
    }

    /**
     * 缓存数据
     * @param $name
     * @param null $value
     * @return mixed|null
     */
    public static function cacheData($name, $value = null)
    {
        $name = "cacheData::" . $name;
        $tmp = Tmp::whereName($name)->select('value')->first();
        if ($value !== null) {
            if (empty($tmp)) {
                Tmp::createInstance(['name' => $name, 'value' => $value])->save();
            } else {
                Tmp::whereName($name)->update(['value' => $value]);
            }
            return $value;
        } else {
            return $tmp->value;
        }
    }

    /**
     * 计算两点地理坐标之间的距离
     * @param float $startLong 起点经度
     * @param float $startLat 起点纬度
     * @param float $endLong 终点经度
     * @param float $endLat 终点纬度
     * @param Int $unit 单位 1:米 2:公里
     * @param Int $decimal 精度 保留小数位数
     * @return float
     */
    public static function getDistance(float $startLong, float $startLat, float $endLong, float $endLat, $unit = 2, $decimal = 2): float
    {

        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;

        $radLat1 = $startLat * $PI / 180.0;
        $radLat2 = $endLat * $PI / 180.0;

        $radLng1 = $startLong * $PI / 180.0;
        $radLng2 = $endLong * $PI / 180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if ($unit == 2) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }

    /**
     * 处理ping原格式内容
     * @param $original
     * @return array
     */
    public static function handlePingOriginal($original)
    {
        $original = trim($original);
        $ipSpeeds = [];
        if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s*:\s*xmt\/rcv\/\%loss/i', $original)) {
            $strings = explode("\n", $original);
            foreach ($strings as $string) {
                preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s*:\s*xmt\/rcv\/\%loss\s*\=\s*(.*?)%(\,\s*min\/avg\/max\s*\=\s*(\d+.?\d+)\/(\d+.?\d+)\/(\d+.?\d+))*/i", $string, $match);
                if ($match) {
                    $ipSpeeds[$match[1]] = isset($match[5]) ? max(1, intval($match[5])) : 0;
                }
            }
        } else {
            $strings = preg_split("/--- (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) ping statistics ---/",
                $original,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            for ($i = 0; $i < count($strings); $i += 2) {
                $context = $strings[$i + 1];
                if ($context) {
                    preg_match("/p\(\d+\) = (\d+),/i", $context, $matches);
                    if ($matches && intval($matches[1]) > 0) {
                        $ipSpeeds[$strings[$i]] = max(1, intval($matches[1]));
                        continue;
                    }
                    preg_match("/min\/avg\/max\/(sdev|stddev)\s*\=\s*(\d+.?\d+)\/(\d+.?\d+)\/(\d+.?\d+)\//i", $context, $matches);
                    if ($matches && intval($matches[3]) > 0) {
                        $ipSpeeds[$strings[$i]] = max(1, intval($matches[3]));
                    } elseif (Base::strExists($context, '100% packet loss') || Base::strExists($context, '100.00% packet loss')) {
                        $ipSpeeds[$strings[$i]] = 0;
                    }
                }
            }
        }
        return $ipSpeeds;
    }

    /**
     * 将IP转换为十六进制数
     * @param $ip
     * @return string
     */
    public static function ip2hex($ip)
    {
        $hex = '';
        $arr = explode('.', $ip);//分隔ip段
        foreach ($arr as $value) {
            $ipHex = dechex($value); // 将每段ip转换成16进制
            if (strlen($ipHex) < 2) {
                $ipHex = '0' . $ipHex;//如果转换后的16进制数长度小于2，在其前面加一个0
            }
            $hex .= $ipHex; // 将四段IP的16进制数连接起来，得到一个16进制字符串，长度为8
        }
        return $hex;
    }

    /**
     * 字符串配置项转数组
     * @param String $stringConfig
     * @return array
     */
    public static function stringConfig2Array(string $stringConfig): array
    {
        $strings = preg_split("/\[(.*?)\]/",
            trim($stringConfig),
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $data = [];
        for ($i = 0; $i < count($strings); $i += 2) {
            $context = $strings[$i + 1];
            if ($context) {
                preg_match_all("/(.*?)\s*=\s*(.*?)(\n|$)/s", trim($context), $matches);
                if ($matches && intval($matches[1]) > 0) {
                    if (!isset($data[$strings[$i]])) {
                        $data[$strings[$i]] = [];
                    }
                    $tmp = [];
                    foreach ($matches[1] as $index => $match) {
                        $tmp[$match] = $matches[2][$index];
                    }
                    $data[$strings[$i]][] = $tmp;
                }
            }
        }
        return $data;
    }

    /**
     * 二维数组交叉排列组合
     * @param array $ip
     * @return array
     */
    public static function crossJoin(array $ip)
    {
        if (empty($ip)) {
            return [];
        }
        $collection = collect($ip);
        $matrix = $collection->crossJoin($ip)->toArray();

        // 排序
        foreach ($matrix as &$item) {
            sort($item);
        }

        // 删除相同数组
        unset($item);
        foreach ($matrix as $key => $item) {
            if ($item[0] == $item[1]) {
                unset($matrix[$key]);
            }
        }
        $matrix = array_unique($matrix, SORT_REGULAR);
        return array_merge($matrix);
    }

    /**
     * 字节转格式
     * @param $bytes
     * @return string
     */
    public static function readableBytes($bytes)
    {
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }

    /**
     * 去除emoji表情
     * @param $str
     * @return string|string[]|null
     */
    public static function filterEmoji($str)
    {
        return preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
    }

    /**
     * 统一验证器
     * @param $data
     * @param $messages
     */
    public static function validator($data, $messages) {
        $rules = [];
        foreach ($messages as $key => $item) {
            $keys = explode(".", $key);
            if (isset($keys[1])) {
                if (isset($rules[$keys[0]])) {
                    $rules[$keys[0]] = $rules[$keys[0]] . '|' . $keys[1];
                } else {
                    $rules[$keys[0]] = $keys[1];
                }
            }
        }
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ApiException($validator->errors()->first());
        }
    }

    /**
     * 流下载，解决没有后缀无法下载的问题
     * @param $file
     * @param $name
     * @return mixed
     */
    public static function streamDownload($file, $name = null)
    {
        if ($name && !str_contains($name, '.')) {
            $name .= ".";
        }
        //
        if (!$file instanceof File) {
            if ($file instanceof \SplFileInfo) {
                $file = new File($file->getPathname());
            } else {
                $file = new File((string)$file);
            }
        }
        if (!$file->isReadable()) {
            throw new FileException('File must be readable.');
        }
        // 大于100M直接下载
        if ($file->getSize() > 100 * 1024 * 1024) {
            return Response::download($file->getPathname(), $name);
        }
        //
        $filePath = $file->getPathname();
        return Response::stream(function () use ($filePath) {
            $fileStream = fopen($filePath, 'r');
            while (!feof($fileStream)) {
                echo fread($fileStream, 1024);
                flush();
            }
            fclose($fileStream);
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Content-Length' => $file->getSize(),
        ]);
    }

    /**
     * 保存图片到文件（同时压缩）
     * @param $path
     * @param $content
     * @param int $quality  压缩图片质量(默认：0不压缩)
     * @return bool
     */
    public static function saveContentImage($path, $content, int $quality = 0) {
        if (file_put_contents($path, $content)) {
            if ($quality > 0) {
                Image::compressImage($path, null, $quality);
            }
            return true;
        }
        return false;
    }

    /**
     * 多维数组字母转下划线格式
     * @param $array
     * @return array
     */
    public static function arrayKeyToUnderline($array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            //如果是数组，递归调用
            if (is_array($value)) {
                $value = self::arrayKeyToUnderline($value);
            }
            $newKey = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $key));
            $newArray[$newKey] = $value;
        }
        return $newArray;
    }

    /**
     * 多维数组字母转驼峰格式
     *
     * @param [type] $array
     * @return array
     */
    public static function arrayKeyToCamel($array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            //如果是数组，递归调用
            if (is_array($value)) {
                $value = self::arrayKeyToCamel($value);
            }
            $newKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $newArray[$newKey] = $value;
        }
        return $newArray;
    }

    /**
     * MD(markdown) 转 html
     * @param $markdown
     * @return \League\CommonMark\Output\RenderedContentInterface|mixed
     */
    public static function markdown2html($markdown)
    {
        $converter = new CommonMarkConverter();
        try {
            return $converter->convert($markdown);
        } catch (CommonMarkException $e) {
            return $markdown;
        }
    }
}
