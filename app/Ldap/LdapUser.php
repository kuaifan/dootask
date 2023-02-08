<?php

namespace App\Ldap;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Module\Base;
use LdapRecord\Container;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\Model;

class LdapUser extends Model
{
    protected static bool $init = false;
    /**
     * The object classes of the LDAP model.
     *
     * @var array
     */
    public static $objectClasses = [
        'inetOrgPerson',
        'organizationalPerson',
        'person',
        'top',
        'posixAccount',
    ];

    /**
     * @return LdapUser
     */
    public static function static(): LdapUser
    {
        return new static;
    }

    /**
     * 服务是否打开
     * @return bool
     */
    public static function isOpen(): bool
    {
        $setting = Base::setting('thirdAccessSetting');
        return $setting['ldap_open'] === 'open';
    }

    /**
     * 初始化配置
     * @return void
     * @throws \LdapRecord\Configuration\ConfigurationException
     */
    public static function initConfig()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        //
        $setting = Base::setting('thirdAccessSetting');
        $connection = Container::getDefaultConnection();
        $connection->setConfiguration([
            "hosts" => [$setting['ldap_host']],
            "port" => intval($setting['ldap_port']),
            "password" => $setting['ldap_password'],
            "username" => $setting['ldap_cn'],
            "base_dn" => $setting['ldap_dn'],
        ]);
    }

    /**
     * 登录
     * @param $username
     * @param $password
     * @param User|null $user
     * @return User|mixed|null
     * @throws \LdapRecord\Configuration\ConfigurationException
     */
    public static function userLogin($username, $password, $user = null)
    {
        self::initConfig();
        $row = self::static()
            ->where([
                'cn' => $username,
                'userPassword' => $password
            ])->first();
        if (!$row) {
            return null;
        }
        if ($user) {
            return $user;
        }
        return User::reg($username, Base::generatePassword(32));
    }

    /**
     * 添加
     * @param $userid
     * @param $username
     * @param $password
     * @param $description
     * @return void
     * @throws \LdapRecord\Configuration\ConfigurationException
     */
    public static function userReg($userid, $username, $password, $description = '')
    {
        self::initConfig();
        try {
            self::static()->create([
                'cn' => $username,
                'gidNumber' => 0,
                'homeDirectory' => '/home/ldap/dootask/' . env("APP_NAME"),
                'sn' => $username,
                'uid' => $username,
                'uidNumber' => $userid,
                'userPassword' => $password,
                'description' => $description,
            ]);
        } catch (LdapRecordException $e) {
            throw new ApiException("reg ldap fail: " . $e->getMessage());
        }
    }

    /**
     * 更新
     * @param $username
     * @param $array
     * @return void
     * @throws \LdapRecord\Configuration\ConfigurationException
     */
    public static function userUpdate($username, $array)
    {
        self::initConfig();
        $row = self::static()
            ->where([
                'cn' => $username,
            ])->first();
        try {
            $row?->update($array);
        } catch (LdapRecordException $e) {
            throw new ApiException("update ldap fail: " . $e->getMessage());
        }
    }

    /**
     * 删除
     * @param $username
     * @return void
     * @throws \LdapRecord\Configuration\ConfigurationException
     */
    public static function userDelete($username)
    {
        self::initConfig();
        $row = self::static()
            ->where([
                'cn' => $username,
            ])->first();
        try {
            $row?->delete();
        } catch (LdapRecordException $e) {
            throw new ApiException("delete ldap fail: " . $e->getMessage());
        }
    }
}
