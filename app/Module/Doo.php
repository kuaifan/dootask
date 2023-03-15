<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\User;
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
                void initialize(char* work, char* lang);
                void setUserToken(char* val);
                char* license();
                char* licenseDecode(char* license);
                bool licenseSave(char* license);
                int userId();
                char* userExpiredAt();
                char* userEmail();
                char* userToken();
                char* userCreate(char* email, char* password);
                char* tokenEncode(int userid, char* email, char* encrypt, int days);
                char* tokenDecode(char* val);
                char* translate(char* val, char* val);
                char* md5s(char* text, char* password);
            EOF, app_path("Module/Lib/doo.so"));
            $doo->initialize("/var/www", Base::headerOrInput('language'));
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
     * 解析License
     * @param $license
     * @return array
     */
    public static function licenseDecode($license)
    {
        return Base::json2array(self::string(self::init()->licenseDecode($license)));
    }

    /**
     * 保存License
     * @param $license
     * @return bool
     */
    public static function licenseSave($license)
    {
        return (bool)self::init()->licenseSave($license);
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
     * 创建帐号
     * @param $email
     * @param $password
     * @return User|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function userCreate($email, $password)
    {
        $data = Base::json2array(self::string(self::init()->userCreate($email, $password)));
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
     * @param string $type
     * @return string
     */
    public static function translate($text, $type = "")
    {
        return self::string(self::init()->translate($text, $type));
    }

    /**
     * md5防破解
     * @param string $text
     * @param string $password
     * @return string
     */
    public static function md5s($text, $password = "")
    {
        return self::string(self::init()->md5s($text, $password));
    }
}
