<?php

namespace App\Module;

use App\Models\User;
use App\Module\Interface\DooSo;
use App\Services\RequestContext;

class Doo
{
    private const DOO_INSTANCE = 'doo_instance';
    private const DOO_LANGUAGE = 'doo_language';

    /**
     * 加载Doo实例
     * - 如果已经存在，则直接返回
     * - 否则，创建一个新的FFI实例，并初始化
     * @param $token
     * @param $language
     * @return DooSo
     */
    public static function load($token = null, $language = null): DooSo
    {
        if (RequestContext::has(self::DOO_INSTANCE)) {
            return RequestContext::get(self::DOO_INSTANCE);
        }

        $request = request();
        if ($request && method_exists($request, 'header')) {
            $token = $token ?: Base::token();
            $language = $language ?: Base::headerOrInput('language');
        }
        $instance = new DooSo($token, $language);

        RequestContext::set(self::DOO_INSTANCE, $instance);
        RequestContext::set(self::DOO_LANGUAGE, $language);

        return $instance;
    }

    /**
     * License
     * @return array
     */
    public static function license(): array
    {
        return self::load()->license();
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
     * 保存License
     * @param $license
     */
    public static function licenseSave($license): void
    {
        self::load()->licenseSave($license);
    }

    /**
     * 当前会员ID（来自请求的token）
     * @return int
     */
    public static function userId(): int
    {
        return self::load()->userId();
    }

    /**
     * token是否过期（来自请求的token）
     * @return bool
     */
    public static function userExpired(): bool
    {
        return self::load()->userExpired();
    }

    /**
     * token过期时间（来自请求的token）
     * @return string|null
     */
    public static function userExpiredAt(): ?string
    {
        return self::load()->userExpiredAt();
    }

    /**
     * 当前会员邮箱地址（来自请求的token）
     * @return string
     */
    public static function userEmail(): string
    {
        return self::load()->userEmail();
    }

    /**
     * 当前会员Encrypt（来自请求的token）
     * @return string
     */
    public static function userEncrypt(): string
    {
        return self::load()->userEncrypt();
    }

    /**
     * 当前会员token（来自请求的token）
     * @return string
     */
    public static function userToken(): string
    {
        return self::load()->userToken();
    }

    /**
     * 创建帐号
     * @param $email
     * @param $password
     * @return User|null
     */
    public static function userCreate($email, $password): User|null
    {
        return self::load()->userCreate($email, $password);
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
        return self::load()->tokenEncode($userid, $email, $encrypt, $days);
    }

    /**
     * 解码token
     * @param $token
     * @return array
     */
    public static function tokenDecode($token): array
    {
        return self::load()->tokenDecode($token);
    }

    /**
     * 翻译
     * @param $text
     * @param ?string $lang
     * @return string
     */
    public static function translate($text, ?string $lang = ""): string
    {
        if (empty($lang)) {
            $lang = RequestContext::get(self::DOO_LANGUAGE);
        }
        return self::load()->translate($text, $lang);
    }

    /**
     * 设置语言
     * @param int|string $lang 语言 或 会员ID
     * @return void
     */
    public static function setLanguage(int|string $lang): void
    {
        if (Base::isNumber($lang)) {
            $lang = User::find(intval($lang))?->lang ?: "";
        }
        RequestContext::set(self::DOO_LANGUAGE, $lang);
    }

    /**
     * 获取语言列表 或 语言名称
     * @param bool|string $lang
     * @return string|string[]
     */
    public static function getLanguages(bool|string $lang = false): array|string
    {
        $array = [
            "zh" => "简体中文",
            "zh-CHT" => "繁体中文",
            "en" => "英语",
            "ko" => "韩语",
            "ja" => "日语",
            "de" => "德语",
            "fr" => "法语",
            "id" => "印度尼西亚语",
            "ru" => "俄语",
        ];
        if ($lang !== false) {
            return $array[$lang] ?? "";
        }
        return $array;
    }

    /**
     * 检查语言是否存在
     * @param $lang
     * @return bool
     */
    public static function checkLanguage($lang): bool
    {
        return array_key_exists($lang, self::getLanguages());
    }

    /**
     * md5防破解
     * @param $text
     * @param string $password
     * @return string
     */
    public static function md5s($text, string $password = ""): string
    {
        return self::load()->md5s($text, $password);
    }

    /**
     * 获取php容器mac地址组
     * @return array
     */
    public static function macs(): array
    {
        return self::load()->macs();
    }

    /**
     * 获取当前SN
     * @return string
     */
    public static function dooSN(): string
    {
        return self::load()->dooSN();
    }

    /**
     * 获取当前版本
     * @return string
     */
    public static function dooVersion(): string
    {
        return self::load()->dooVersion();
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
        return self::load()->pgpGenerateKeyPair($name, $email, $passphrase);
    }

    /**
     * PGP加密
     * @param $plaintext
     * @param $publicKey
     * @return string
     */
    public static function pgpEncrypt($plaintext, $publicKey): string
    {
        return self::load()->pgpEncrypt($plaintext, $publicKey);
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
        return self::load()->pgpDecrypt($encryptedText, $privateKey, $passphrase);
    }

    /**
     * PGP加密API
     * @param $plaintext
     * @param $publicKey
     * @return string
     */
    public static function pgpEncryptApi($plaintext, $publicKey): string
    {
        return self::load()->pgpEncryptApi($plaintext, $publicKey);
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
        return self::load()->pgpDecryptApi($encryptedText, $privateKey, $passphrase);
    }

    /**
     * 解析PGP参数
     * @param $string
     * @return string[]
     */
    public static function pgpParseStr($string): array
    {
        return self::load()->pgpParseStr($string);
    }

    /**
     * 还原公钥格式
     * @param $key
     * @return string
     */
    public static function pgpPublicFormat($key): string
    {
        return self::load()->pgpPublicFormat($key);
    }
}
