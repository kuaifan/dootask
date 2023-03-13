<?php

namespace App\Module;

use Carbon\Carbon;
use FFI;

class Doo
{
    public static $doo = null;

    public function __construct()
    {
        return self::init();
    }

    /**
     * 初始化
     * @return mixed
     */
    public static function init()
    {
        if (self::$doo === null) {
            $doo = FFI::cdef(<<<EOF
                void initialize();
                void setWorkDir(char* val);
                void setDefaultLanguage(char* val);
                void setUserToken(char* val);
                char* license();
                int userId();
                char* userExpiredAt();
                char* userEmail();
                char* userToken();
                char* tokenEncode(int userid, char* email, char* encrypt, int days);
                char* tokenDecode(char* val);
                char* translate(char* val);
                char* translateSpecified(char* val, char* val);
            EOF, app_path("Module/Lib/doo.so"));
            $doo->initialize();
            $doo->setWorkDir("/var/www");
            $doo->setDefaultLanguage(Base::headerOrInput('language'));
            $doo->setUserToken(Base::getToken());
            self::$doo = $doo;
        }
        return self::$doo;
    }

    /**
     * char转为字符串
     * @param $text
     * @return string
     */
    public static function string($text)
    {
        return FFI::string($text);
    }

    /**
     * License
     * @return array
     */
    public static function license()
    {
        $array = Base::json2array(self::string(self::init()->license()));

        $ips = explode(",", $array['ip']);
        $array['ip'] = [];
        foreach ($ips as $ip) {
            if (Base::is_ipv4($ip)) {
                $array['ip'][] = $ip;
            }
        }

        $domains = explode(",", $array['domain']);
        $array['domain'] = [];
        foreach ($domains as $domain) {
            if (Base::is_domain($domain)) {
                $array['domain'][] = $domain;
            }
        }

        $emails = explode(",", $array['email']);
        $array['email'] = [];
        foreach ($emails as $email) {
            if (Base::isEmail($email)) {
                $array['email'][] = $email;
            }
        }

        return $array;
    }

    /**
     * 当前会员ID（来自请求的token）
     * @return int
     */
    public static function userId()
    {
        return self::init()->userId();
    }

    /**
     * token是否过期（来自请求的token）
     * @return bool
     */
    public static function userExpired()
    {
        return Carbon::parse(self::string(self::init()->userExpiredAt()))->isBefore(Carbon::now());
    }

    /**
     * 当前会员邮箱地址（来自请求的token）
     * @return string
     */
    public static function userEmail()
    {
        return self::string(self::init()->userEmail());
    }

    /**
     * 当前会员token（来自请求的token）
     * @return string
     */
    public static function userToken()
    {
        return self::string(self::init()->userToken());
    }

    /**
     * 生成token（编码token）
     * @param $userid
     * @param $email
     * @param $encrypt
     * @param int $days 有效时间（天）
     * @return string
     */
    public static function tokenEncode($userid, $email, $encrypt, $days = 7)
    {
        return self::string(self::init()->tokenEncode($userid, $email, $encrypt, $days));
    }

    /**
     * 解码token
     * @param $token
     * @return array
     */
    public static function tokenDecode($token)
    {
        $array = Base::json2array(self::string(self::init()->tokenDecode($token)));
        $array['is_expired'] = Carbon::parse($array['expired_at'])->isBefore(Carbon::now());
        return $array;
    }


    /**
     * 翻译
     * @param string $text
     * @param string|null $type
     * @return string
     */
    public static function translate($text, $type = null)
    {
        if ($type) {
            $test = self::init()->translateSpecified($text, $type);
        } else {
            $test = self::init()->translate($text);
        }
        return self::string($test);
    }
}
