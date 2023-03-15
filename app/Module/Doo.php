<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\User;
use Carbon\Carbon;
use FFI;

class Doo
{
    private static $doo = null;
    private static $token = null;
    private static $language = null;

    /**
     * 加载模块
     * @param $token
     * @param $language
     * @return null
     */
    public static function load($token = null, $language = null)
    {
        if (self::$doo === null) {
            self::$doo = FFI::cdef(<<<EOF
                void initialize(char* work, char* token, char* lang);
                char* license();
                char* licenseDecode(char* license);
                bool licenseSave(char* license);
                int userId();
                char* userExpiredAt();
                char* userEmail();
                char* userEncrypt();
                char* userToken();
                char* userCreate(char* email, char* password);
                char* tokenEncode(int userid, char* email, char* encrypt, int days);
                char* tokenDecode(char* val);
                char* translate(char* val, char* val);
                char* md5s(char* text, char* password);
                EOF, app_path("Module/Lib/doo.so"));
            self::$token = $token ?: Base::headerOrInput('token');
            self::$language = $language ?: Base::headerOrInput('language');
        }
        self::$doo->initialize("/var/www", self::$token, self::$language);
        return self::$doo;
    }

    /**
     * char转为字符串
     * @param $text
     * @return string
     */
    private static function string($text)
    {
        return FFI::string($text);
    }

    /**
     * License
     * @return array
     */
    public static function license()
    {
        $array = Base::json2array(self::string(self::load()->license()));

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
     * 解析License
     * @param $license
     * @return array
     */
    public static function licenseDecode($license)
    {
        return Base::json2array(self::string(self::load()->licenseDecode($license)));
    }

    /**
     * 保存License
     * @param $license
     * @return bool
     */
    public static function licenseSave($license)
    {
        return (bool)self::load()->licenseSave($license);
    }

    /**
     * 当前会员ID（来自请求的token）
     * @return int
     */
    public static function userId()
    {
        return intval(self::load()->userId());
    }

    /**
     * token是否过期（来自请求的token）
     * @return bool
     */
    public static function userExpired()
    {
        $expiredAt = self::userExpiredAt();
        return $expiredAt != 'forever' && Carbon::parse($expiredAt)->isBefore(Carbon::now());
    }

    /**
     * token过期时间（来自请求的token）
     * @return bool
     */
    public static function userExpiredAt()
    {
        return self::string(self::load()->userExpiredAt());
    }

    /**
     * 当前会员邮箱地址（来自请求的token）
     * @return string
     */
    public static function userEmail()
    {
        return self::string(self::load()->userEmail());
    }

    /**
     * 当前会员Encrypt（来自请求的token）
     * @return string
     */
    public static function userEncrypt()
    {
        return self::string(self::load()->userEncrypt());
    }

    /**
     * 当前会员token（来自请求的token）
     * @return string
     */
    public static function userToken()
    {
        return self::string(self::load()->userToken());
    }

    /**
     * 创建帐号
     * @param $email
     * @param $password
     * @return User|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function userCreate($email, $password)
    {
        $data = Base::json2array(self::string(self::load()->userCreate($email, $password)));
        if (Base::isError($data)) {
            throw new ApiException($data['msg'] ?: '注册失败');
        }
        $user = User::whereEmail($email)->first();
        if (empty($user)) {
            throw new ApiException('注册失败');
        }
        return $user;
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
        return self::string(self::load()->tokenEncode($userid, $email, $encrypt, $days));
    }

    /**
     * 解码token
     * @param $token
     * @return array
     */
    public static function tokenDecode($token)
    {
        return Base::json2array(self::string(self::load()->tokenDecode($token)));
    }

    /**
     * 翻译
     * @param string $text
     * @param string $type
     * @return string
     */
    public static function translate($text, $type = "")
    {
        return self::string(self::load()->translate($text, $type));
    }

    /**
     * md5防破解
     * @param string $text
     * @param string $password
     * @return string
     */
    public static function md5s($text, $password = "")
    {
        return self::string(self::load()->md5s($text, $password));
    }
}
