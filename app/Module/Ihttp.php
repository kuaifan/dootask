<?php

namespace App\Module;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class Ihttp
{

    public static function ihttp_request($url, $post = [], $extra = [], $timeout = 60, $retRaw = false, $isGb2312 = false) {
        $urlset = parse_url($url);
        if(empty($urlset['path'])) {
            $urlset['path'] = '/';
        }
        if(!empty($urlset['query'])) {
            $urlset['query'] = "?{$urlset['query']}";
        } else {
            $urlset['query'] = '';
        }
        if(empty($urlset['port'])) {
            $urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
        }
        if (Base::strExists($url, 'https://') && !extension_loaded('openssl')) {
            if (!extension_loaded("openssl")) {
                return Base::retError('请开启您PHP环境的openssl');
            }
        }
        Base::addLog([
            'url' => $url,
            'post' => $post,
        ], 'before_' . $url);
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlset['scheme']. '://' .$urlset['host'].($urlset['port'] == '80' ? '' : ':'.$urlset['port']).$urlset['path'].$urlset['query']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            if($post) {
                if (is_array($post)) {
                    $filepost = false;
                    foreach ($post as $value) {
                        if (is_string($value) && str_starts_with($value, '@')) {
                            $filepost = true;
                            break;
                        }
                        if ($value instanceof \CURLFile) {
                            $filepost = true;
                            break;
                        }
                    }
                    if (!$filepost) {
                        $post = http_build_query($post);
                    }
                }
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            // 启用证书链与主机名校验，防止中间人篡改/窃听通过本客户端发出的请求
            // （AI 密钥、授权令牌、提示词与响应内容等均经由此路径传输）
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
            if (!empty($extra) && is_array($extra)) {
                $headers = array();
                foreach ($extra as $opt => $value) {
                    if (Base::strExists($opt, 'CURLOPT_')) {
                        curl_setopt($ch, constant($opt), $value);
                    } elseif (is_numeric($opt)) {
                        curl_setopt($ch, $opt, $value);
                    } else {
                        $headers[] = "{$opt}: {$value}";
                    }
                }
                if(!empty($headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
            }
            $data = curl_exec($ch);
            //$status = curl_getinfo($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if($errno || empty($data)) {
                Base::addLog([
                    'url' => $url,
                    'post' => $post,
                    'error' => $error,
                ], 'error_' . $url);
                return Base::retError($error);
            } else {
                if ($isGb2312) {
                    try { $data = iconv('GB2312', 'UTF-8', $data); }catch (\Throwable) { }
                }
                $response = self::ihttp_response_parse($data);
                Base::addLog([
                    'url' => $url,
                    'post' => $post,
                    'response' => $response,
                ], 'success_' . $url);
                if ($retRaw === true) {
                    return Base::retSuccess($response['code'], $response);
                }
                return Base::retSuccess($response['code'], $response['content']);
            }
        }
        $method = empty($post) ? 'GET' : 'POST';
        $fdata = "{$method} {$urlset['path']}{$urlset['query']} HTTP/1.1\r\n";
        $fdata .= "Host: {$urlset['host']}\r\n";
        if(function_exists('gzdecode')) {
            $fdata .= "Accept-Encoding: gzip, deflate\r\n";
        }
        $fdata .= "Connection: close\r\n";
        if (!empty($extra) && is_array($extra)) {
            foreach ($extra as $opt => $value) {
                if (!Base::strExists($opt, 'CURLOPT_')) {
                    $fdata .= "{$opt}: {$value}\r\n";
                }
            }
        }
        //$body = '';
        if ($post) {
            if (is_array($post)) {
                $body = http_build_query($post);
            } else {
                $body = urlencode((string)$post);
            }
            $fdata .= 'Content-Length: ' . strlen($body) . "\r\n\r\n{$body}";
        } else {
            $fdata .= "\r\n";
        }
        if($urlset['scheme'] == 'https') {
            $fp = fsockopen('ssl://' . $urlset['host'], $urlset['port'], $errno, $error);
        } else {
            $fp = fsockopen($urlset['host'], $urlset['port'], $errno, $error);
        }
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);
        if (!$fp) {
            Base::addLog([
                'url' => $url,
                'post' => $post,
                'error' => $error,
            ], 'error_' . $url);
            return Base::retError( $error);
        } else {
            fwrite($fp, $fdata);
            $content = '';
            while (!feof($fp))
                $content .= fgets($fp, 512);
            fclose($fp);
            if ($isGb2312) {
                try { $content = iconv('GB2312', 'UTF-8', $content); }catch (\Throwable) { }
            }
            $response = self::ihttp_response_parse($content, true);
            Base::addLog([
                'url' => $url,
                'post' => $post,
                'response' => $response,
            ], 'success_' . $url);
            if ($retRaw === true) {
                return Base::retSuccess($response['code'], $response);
            }
            return Base::retSuccess($response['code'], $response['content']);
        }
    }

    public static function ihttp_get($url) {
        return self::ihttp_request($url);
    }

    public static function ihttp_post($url, $data, $timeout = 60) {
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
        return self::ihttp_request($url, $data, $headers, $timeout);
    }

    public static function ihttp_proxy($url, $post = [], $timeout = 20, $extra = []) {
        return self::ihttp_request($url, $post, $extra, $timeout);
    }

    private static function ihttp_response_parse($data, $chunked = false) {
        $rlt = array();
        $pos = strpos($data, "\r\n\r\n");
        if (Base::strExists(substr($data, 0, $pos), "Proxy-agent:")) {
            $data = substr($data, $pos + 4, strlen($data));
            $pos = strpos($data, "\r\n\r\n");
        }
        $split1[0] = substr($data, 0, $pos);
        $split1[1] = substr($data, $pos + 4, strlen($data));

        $split2 = explode("\r\n", $split1[0], 2);
        preg_match('/^(\S+) (\S+)(?: (.*))?$/', $split2[0], $matches);
        $rlt['code'] = $matches[2];
        $rlt['status'] = $matches[3] ?? '';
        $rlt['responseline'] = $split2[0];
        $header = explode("\r\n", $split2[1]);
        $rlt['headers'] = [];
        $isgzip = false;
        $ischunk = false;
        foreach ($header as $v) {
            $row = explode(':', $v);
            $key = trim($row[0]);
            $value = trim(substr($v, strlen($row[0]) + 1));
            if (isset($rlt['headers'][$key]) && is_array($rlt['headers'][$key])) {
                $rlt['headers'][$key][] = $value;
            } elseif (!empty($rlt['headers'][$key])) {
                $temp = $rlt['headers'][$key];
                unset($rlt['headers'][$key]);
                $rlt['headers'][$key][] = $temp;
                $rlt['headers'][$key][] = $value;
            } else {
                $rlt['headers'][$key] = $value;
            }
            if(!$isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
                $isgzip = true;
            }
            if(!$ischunk && strtolower($key) == 'transfer-encoding' && strtolower($value) == 'chunked') {
                $ischunk = true;
            }
        }
        if($chunked && $ischunk) {
            $rlt['content'] = self::ihttp_response_parse_unchunk($split1[1]);
        } else {
            $rlt['content'] = $split1[1];
        }
        if($isgzip && function_exists('gzdecode')) {
            $rlt['content'] = gzdecode($rlt['content']);
        }

        $rlt['meta'] = $data;
        if($rlt['code'] == '100') {
            return self::ihttp_response_parse($rlt['content']);
        }
        return $rlt;
    }

    private static function ihttp_response_parse_unchunk($str = null) {
        if(!is_string($str) or strlen($str) < 1) {
            return false;
        }
        $eol = "\r\n";
        $add = strlen($eol);
        $tmp = $str;
        $str = '';
        do {
            $tmp = ltrim($tmp);
            $pos = strpos($tmp, $eol);
            if($pos === false) {
                return false;
            }
            $len = hexdec(substr($tmp, 0, $pos));
            if(!is_numeric($len) or $len < 0) {
                return false;
            }
            $str .= substr($tmp, ($pos + $add), $len);
            $tmp  = substr($tmp, ($len + $pos + $add));
            $check = trim($tmp);
        } while(!empty($check));
        unset($tmp);
        return $str;
    }

    /**
     * 下载文件到服务器本地
     * @param string $url 下载地址
     * @param string $fileFile 保存文件路径
     * @return array
     */
    public static function download(string $url, string $fileFile)
    {
        if ($url == '') {
            return Base::retError("url error");
        }

        // 获取远程文件资源
        $ch = curl_init();
        $timeout = 30;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file = curl_exec($ch);
        curl_close($ch);

        // 保存文件
        $res = fopen($fileFile, 'a');
        fwrite($res, $file);
        fclose($res);

        return Base::retSuccess('success');
    }

    /**
     * 判断一个 URL 是否仅指向公网地址（SSRF 防护）
     *
     * 仅允许 http/https；主机名会解析全部 A/AAAA 记录，只要有一个解析结果
     * 落在私有/保留/环回/链路本地范围内就拒绝（防止多应答/DNS rebinding
     * 绕过）。同时识别通过 IPv6 编码嵌入的私有 IPv4 地址（IPv4-mapped、
     * 6to4、NAT64 well-known prefix），这类地址不会被
     * FILTER_FLAG_NO_PRIV_RANGE/FILTER_FLAG_NO_RES_RANGE 直接拦截。
     *
     * @param string $url
     * @return bool
     */
    public static function isPublicDestination(string $url): bool
    {
        $parts = @parse_url($url);
        if (empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        if (empty($parts['host'])) {
            return false;
        }
        $host = $parts['host'];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || empty($records)) {
            return false;
        }
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (!$ip || !self::isPublicIp($ip)) {
                return false;
            }
        }
        return true;
    }

    private static function isPublicIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = @inet_pton($ip);
            if ($packed !== false && strlen($packed) === 16) {
                $embedded = null;
                if (substr($packed, 0, 12) === hex2bin('00000000000000000000ffff')) {
                    // IPv4-mapped (::ffff:a.b.c.d)
                    $embedded = inet_ntop(substr($packed, 12, 4));
                } elseif (substr($packed, 0, 2) === hex2bin('2002')) {
                    // 6to4 (2002:aabb:ccdd::/16, aabb:ccdd packs a.b.c.d)
                    $embedded = inet_ntop(substr($packed, 2, 4));
                } elseif (substr($packed, 0, 12) === hex2bin('0064ff9b0000000000000000')) {
                    // NAT64 well-known prefix (64:ff9b::/96)
                    $embedded = inet_ntop(substr($packed, 12, 4));
                }
                if ($embedded !== null && !filter_var($embedded, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return false;
                }
            }
        }
        return true;
    }
}
