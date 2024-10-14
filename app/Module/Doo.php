<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\User;
use Cache;
use Carbon\Carbon;
use FFI;

class Doo
{
    private static $doo;
    private static $passphrase = "LYHevk5n";

    /**
     * char转为字符串
     * @param $text
     * @return string
     */
    private static function string($text): string
    {
        return FFI::string($text);
    }

    /**
     * 装载
     * @param $token
     * @param $language
     */
    public static function load($token = null, $language = null)
    {
        self::$doo = FFI::cdef(<<<EOF
                void initialize(char* work, char* token, char* lang);
                char* license();
                char* licenseDecode(char* license);
                char* licenseSave(char* license);
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
                char* macs();
                char* dooSN();
                char* pgpGenerateKeyPair(char* name, char* email, char* passphrase);
                char* pgpEncrypt(char* plainText, char* publicKey);
                char* pgpDecrypt(char* cipherText, char* privateKey, char* passphrase);
            EOF, "/usr/lib/doo/doo.so");
        $token = $token ?: Base::token();
        $language = $language ?: Base::headerOrInput('language');
        self::$doo->initialize("/var/www", $token, $language);
    }

    /**
     * 获取实例
     * @param $token
     * @param $language
     * @return mixed
     */
    public static function doo($token = null, $language = null)
    {
        if (self::$doo == null) {
            self::load($token, $language);
        }
        return self::$doo;
    }

    /**
     * License
     * @return array
     */
    public static function license(): array
    {
        $array = Base::json2array(self::string(self::doo()->license()));

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

        $macs = explode(",", $array['mac']);
        $array['mac'] = [];
        foreach ($macs as $mac) {
            if (Base::isMac($mac)) {
                $array['mac'][] = $mac;
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
     * 获取License原文
     * @return string
     */
    public static function licenseContent(): string
    {
        if (env("SYSTEM_LICENSE") == 'hidden') {
            return '';
        }
        $paths = [
            config_path("LICENSE"),
            config_path("license"),
            app_path("LICENSE"),
            app_path("license"),
        ];
        $content = "";
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                break;
            }
        }
        return $content;
    }

    /**
     * 解析License
     * @param $license
     * @return array
     */
    public static function licenseDecode($license): array
    {
        return Base::json2array(self::string(self::doo()->licenseDecode($license)));
    }

    /**
     * 保存License
     * @param $license
     */
    public static function licenseSave($license): void
    {
        $res = self::string(self::doo()->licenseSave($license));
        if ($res != 'success') {
            throw new ApiException($res ?: 'LICENSE 保存失败');
        }
    }

    /**
     * 当前会员ID（来自请求的token）
     * @return int
     */
    public static function userId(): int
    {
        return intval(self::doo()->userId());
    }

    /**
     * token是否过期（来自请求的token）
     * @return bool
     */
    public static function userExpired(): bool
    {
        $expiredAt = self::userExpiredAt();
        return $expiredAt && Carbon::parse($expiredAt)->isBefore(Carbon::now());
    }

    /**
     * token过期时间（来自请求的token）
     * @return string
     */
    public static function userExpiredAt(): string
    {
        $expiredAt = self::string(self::doo()->userExpiredAt());
        return $expiredAt === 'forever' ? '' : $expiredAt;
    }

    /**
     * 当前会员邮箱地址（来自请求的token）
     * @return string
     */
    public static function userEmail(): string
    {
        return self::string(self::doo()->userEmail());
    }

    /**
     * 当前会员Encrypt（来自请求的token）
     * @return string
     */
    public static function userEncrypt(): string
    {
        return self::string(self::doo()->userEncrypt());
    }

    /**
     * 当前会员token（来自请求的token）
     * @return string
     */
    public static function userToken(): string
    {
        return self::string(self::doo()->userToken());
    }

    /**
     * 创建帐号
     * @param $email
     * @param $password
     * @return User|null
     */
    public static function userCreate($email, $password): User|null
    {
        $data = Base::json2array(self::string(self::doo()->userCreate($email, $password)));
        if (Base::isError($data)) {
            throw new ApiException($data['msg'] ?: '注册失败');
        }
        if (\DB::transactionLevel() > 0) {
            try {
                \DB::commit();
                \DB::beginTransaction();
            } catch (\Throwable) {
                // do nothing
            }
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
    public static function tokenEncode($userid, $email, $encrypt, int $days = 15): string
    {
        return self::string(self::doo()->tokenEncode($userid, $email, $encrypt, $days));
    }

    /**
     * 解码token
     * @param $token
     * @return array
     */
    public static function tokenDecode($token): array
    {
        return Base::json2array(self::string(self::doo()->tokenDecode($token)));
    }

    /**
     * 翻译
     * @param $text
     * @param string $type
     * @return string
     */
    public static function translate($text, string $type = ""): string
    {
        return self::string(self::doo()->translate($text, $type));
    }

    /**
     * md5防破解
     * @param $text
     * @param string $password
     * @return string
     */
    public static function md5s($text, string $password = ""): string
    {
        return self::string(self::doo()->md5s($text, $password));
    }

    /**
     * 获取php容器mac地址组
     * @return array
     */
    public static function macs(): array
    {
        $macs = explode(",", self::string(self::doo()->macs()));
        $array = [];
        foreach ($macs as $mac) {
            if (Base::isMac($mac)) {
                $array[] = $mac;
            }
        }
        return $array;
    }

    /**
     * 获取当前SN
     * @return string
     */
    public static function dooSN(): string
    {
        return self::string(self::doo()->dooSN());
    }

    /**
     * 生成PGP密钥对
     * @param $name
     * @param $email
     * @param string $passphrase
     * @return array
     */
    public static function pgpGenerateKeyPair($name, $email, string $passphrase = ""): array
    {
        return Base::json2array(self::string(self::doo()->pgpGenerateKeyPair($name, $email, $passphrase)));
    }

    /**
     * PGP加密
     * @param $plaintext
     * @param $publicKey
     * @return string
     */
    public static function pgpEncrypt($plaintext, $publicKey): string
    {
        if (strlen($publicKey) < 50) {
            $keyCache = Base::json2array(Cache::get("KeyPair::" . $publicKey));
            $publicKey = $keyCache['public_key'];
        }
        return self::string(self::doo()->pgpEncrypt($plaintext, $publicKey));
    }

    /**
     * PGP解密
     * @param $encryptedText
     * @param $privateKey
     * @param null $passphrase
     * @return string
     */
    public static function pgpDecrypt($encryptedText, $privateKey, $passphrase = null): string
    {
        if (strlen($privateKey) < 50) {
            $keyCache = Base::json2array(Cache::get("KeyPair::" . $privateKey));
            $privateKey = $keyCache['private_key'];
            $passphrase = $keyCache['passphrase'];
        }
        return self::string(self::doo()->pgpDecrypt($encryptedText, $privateKey, $passphrase));
    }

    /**
     * PGP加密API
     * @param $plaintext
     * @param $publicKey
     * @return string
     */
    public static function pgpEncryptApi($plaintext, $publicKey): string
    {
        $content = Base::array2json($plaintext);
        $content = self::pgpEncrypt($content, $publicKey);
        return preg_replace("/\s*-----(BEGIN|END) PGP MESSAGE-----\s*/i", "", $content);
    }

    /**
     * PGP解密API
     * @param $encryptedText
     * @param null $privateKey
     * @param null $passphrase
     * @return array
     */
    public static function pgpDecryptApi($encryptedText, $privateKey, $passphrase = null): array
    {
        $content = "-----BEGIN PGP MESSAGE-----\n\n" . $encryptedText . "\n-----END PGP MESSAGE-----";
        $content = self::pgpDecrypt($content, $privateKey, $passphrase);
        return Base::json2array($content);
    }

    /**
     * 解析PGP参数
     * @param $string
     * @return string[]
     */
    public static function pgpParseStr($string): array
    {
        $array = [
            'encrypt_type' => '',
            'encrypt_id' => '',
            'client_type' => '',
            'client_key' => '',
        ];
        $string = str_replace(";", "&", $string);
        parse_str($string, $params);
        foreach ($params as $key => $value) {
            $key = strtolower(trim($key));
            if ($key) {
                $array[$key] = trim($value);
            }
        }
        if ($array['client_type'] === 'pgp' && $array['client_key']) {
            $array['client_key'] = self::pgpPublicFormat($array['client_key']);
        }
        return $array;
    }

    /**
     * 还原公钥格式
     * @param $key
     * @return string
     */
    public static function pgpPublicFormat($key): string
    {
        $key = str_replace(["-", "_", "$"], ["+", "/", "\n"], $key);
        if (!str_contains($key, '-----BEGIN PGP PUBLIC KEY BLOCK-----')) {
            $key = "-----BEGIN PGP PUBLIC KEY BLOCK-----\n\n" . $key . "\n-----END PGP PUBLIC KEY BLOCK-----";
        }
        return $key;
    }
}
