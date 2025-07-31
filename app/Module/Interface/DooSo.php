<?php

namespace App\Module\Interface;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Models\User;
use Cache;
use Carbon\Carbon;
use DB;
use FFI;
use FFI\CData;
use FFI\Exception;
use Throwable;

class DooSo
{
    private mixed $so;

    public function __construct($token = null, $language = null)
    {
        $this->so = FFI::cdef(<<<EOF
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
                char* version();
                char* pgpGenerateKeyPair(char* name, char* email, char* passphrase);
                char* pgpEncrypt(char* plainText, char* publicKey);
                char* pgpDecrypt(char* cipherText, char* privateKey, char* passphrase);
            EOF, "/usr/lib/doo/doo.so");
        $this->so->initialize("/var/www", $token, $language);
        return $this->so;
    }

    /**
     * char转为字符串
     * @param $text
     * @return string
     */
    private static function string($text): string
    {
        if (!($text instanceof CData)) {
            return "";
        }

        try {
            return FFI::string($text);
        } catch (Exception) {
            return "";
        }
    }

    /**
     * License
     * @return array
     */
    public function license(): array
    {
        $array = Base::json2array(self::string($this->so->license()));

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
     * 解析License
     * @param $license
     * @return array
     */
    public function licenseDecode($license): array
    {
        return Base::json2array(self::string($this->so->licenseDecode($license)));
    }

    /**
     * 保存License
     * @param $license
     */
    public function licenseSave($license): void
    {
        $res = self::string($this->so->licenseSave($license));
        if ($res != 'success') {
            throw new ApiException($res ?: 'LICENSE 保存失败');
        }
    }

    /**
     * 当前会员ID（来自请求的token）
     * @return int
     */
    public function userId(): int
    {
        return intval($this->so->userId());
    }

    /**
     * token是否过期（来自请求的token）
     * @return bool
     */
    public function userExpired(): bool
    {
        $expiredAt = $this->userExpiredAt();
        return $expiredAt && Carbon::parse($expiredAt)->isBefore(Carbon::now());
    }

    /**
     * token过期时间（来自请求的token）
     * @return string|null
     */
    public function userExpiredAt(): ?string
    {
        $expiredAt = self::string($this->so->userExpiredAt());
        return $expiredAt === 'forever' ? null : $expiredAt;
    }

    /**
     * 当前会员邮箱地址（来自请求的token）
     * @return string
     */
    public function userEmail(): string
    {
        return self::string($this->so->userEmail());
    }

    /**
     * 当前会员Encrypt（来自请求的token）
     * @return string
     */
    public function userEncrypt(): string
    {
        return self::string($this->so->userEncrypt());
    }

    /**
     * 当前会员token（来自请求的token）
     * @return string
     */
    public function userToken(): string
    {
        return self::string($this->so->userToken());
    }

    /**
     * 创建帐号
     * @param $email
     * @param $password
     * @return User|null
     */
    public function userCreate($email, $password): User|null
    {
        $data = Base::json2array(self::string($this->so->userCreate($email, $password)));
        if (Base::isError($data)) {
            throw new ApiException($data['msg'] ?: '注册失败');
        }
        if (DB::transactionLevel() > 0) {
            try {
                DB::commit();
                DB::beginTransaction();
            } catch (Throwable) {
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
    public function tokenEncode($userid, $email, $encrypt, int $days = 15): string
    {
        return self::string($this->so->tokenEncode($userid, $email, $encrypt, $days));
    }

    /**
     * 解码token
     * @param $token
     * @return array
     */
    public function tokenDecode($token): array
    {
        $array = Base::json2array(self::string($this->so->tokenDecode($token)));
        $array['expired_at'] = $array['expired_at'] === 'forever' ? null : $array['expired_at'];
        return $array;
    }

    /**
     * 翻译
     * @param $text
     * @param ?string $lang
     * @return string
     */
    public function translate($text, ?string $lang = ""): string
    {
        if (empty($text)) {
            return "";
        }
        if (empty($lang)) {
            $lang = "";
        }
        return self::string($this->so->translate($text, $lang));
    }

    /**
     * md5防破解
     * @param $text
     * @param string $password
     * @return string
     */
    public function md5s($text, string $password = ""): string
    {
        return self::string($this->so->md5s($text, $password));
    }

    /**
     * 获取php容器mac地址组
     * @return array
     */
    public function macs(): array
    {
        $macs = explode(",", self::string($this->so->macs()));
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
    public function dooSN(): string
    {
        return self::string($this->so->dooSN());
    }

    /**
     * 获取当前版本
     * @return string
     */
    public function dooVersion(): string
    {
        return self::string($this->so->version());
    }

    /**
     * 生成PGP密钥对
     * @param $name
     * @param $email
     * @param string $passphrase
     * @return array
     */
    public function pgpGenerateKeyPair($name, $email, string $passphrase = ""): array
    {
        return Base::json2array(self::string($this->so->pgpGenerateKeyPair($name, $email, $passphrase)));
    }

    /**
     * PGP加密
     * @param $plaintext
     * @param $publicKey
     * @return string
     */
    public function pgpEncrypt($plaintext, $publicKey): string
    {
        if (strlen($publicKey) < 50) {
            $keyCache = Base::json2array(Cache::get("KeyPair::" . $publicKey));
            $publicKey = $keyCache['public_key'];
        }
        return self::string($this->so->pgpEncrypt($plaintext, $publicKey));
    }

    /**
     * PGP解密
     * @param $encryptedText
     * @param $privateKey
     * @param null $passphrase
     * @return string
     */
    public function pgpDecrypt($encryptedText, $privateKey, $passphrase = null): string
    {
        if (strlen($privateKey) < 50) {
            $keyCache = Base::json2array(Cache::get("KeyPair::" . $privateKey));
            $privateKey = $keyCache['private_key'];
            $passphrase = $keyCache['passphrase'];
        }
        return self::string($this->so->pgpDecrypt($encryptedText, $privateKey, $passphrase));
    }

    /**
     * PGP加密API
     * @param $plaintext
     * @param $publicKey
     * @return string
     */
    public function pgpEncryptApi($plaintext, $publicKey): string
    {
        $content = Base::array2json($plaintext);
        $content = $this->pgpEncrypt($content, $publicKey);
        return preg_replace("/\s*-----(BEGIN|END) PGP MESSAGE-----\s*/i", "", $content);
    }

    /**
     * PGP解密API
     * @param $encryptedText
     * @param null $privateKey
     * @param null $passphrase
     * @return array
     */
    public function pgpDecryptApi($encryptedText, $privateKey, $passphrase = null): array
    {
        $content = "-----BEGIN PGP MESSAGE-----\n\n" . $encryptedText . "\n-----END PGP MESSAGE-----";
        $content = $this->pgpDecrypt($content, $privateKey, $passphrase);
        return Base::json2array($content);
    }

    /**
     * 解析PGP参数
     * @param $string
     * @return string[]
     */
    public function pgpParseStr($string): array
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
            $array['client_key'] = $this->pgpPublicFormat($array['client_key']);
        }
        return $array;
    }

    /**
     * 还原公钥格式
     * @param $key
     * @return string
     */
    public function pgpPublicFormat($key): string
    {
        $key = str_replace(["-", "_", "$"], ["+", "/", "\n"], $key);
        if (!str_contains($key, '-----BEGIN PGP PUBLIC KEY BLOCK-----')) {
            $key = "-----BEGIN PGP PUBLIC KEY BLOCK-----\n\n" . $key . "\n-----END PGP PUBLIC KEY BLOCK-----";
        }
        return $key;
    }
}
