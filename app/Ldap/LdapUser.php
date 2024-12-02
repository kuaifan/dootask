<?php

namespace App\Ldap;

use App\Models\User;
use App\Module\Base;
use LdapRecord\Configuration\ConfigurationException;
use LdapRecord\Container;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\Model;

class LdapUser extends Model
{
    protected static $init = null;
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
     * @return mixed|null
     */
    public function getPhoto()
    {
        return $this->jpegPhoto && is_array($this->jpegPhoto) ? $this->jpegPhoto[0] : null;
    }

    /**
     * @return mixed|null
     */
    public function getDisplayName()
    {
        $nickname = $this->displayName ?: $this->uid;
        return is_array($nickname) ? $nickname[0] : $nickname;
    }

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
        return Base::settingFind('thirdAccessSetting', 'ldap_open') === 'open';
    }

    /**
     * 同步本地是否打开
     * @return bool
     */
    public static function isSyncLocal(): bool
    {
        return Base::settingFind('thirdAccessSetting', 'ldap_sync_local') === 'open';
    }

    /**
     * 初始化配置
     * @return bool
     */
    public static function initConfig()
    {
        if (is_bool(self::$init)) {
            return self::$init;
        }
        //
        $setting = Base::setting('thirdAccessSetting');
        if ($setting['ldap_open'] !== 'open') {
            return self::$init = false;
        }
        //
        $connection = Container::getDefaultConnection();
        try {
            $connection->setConfiguration([
                "hosts" => [$setting['ldap_host']],
                "port" => intval($setting['ldap_port']),
                "base_dn" => $setting['ldap_base_dn'],
                "username" => $setting['ldap_user_dn'],
                "password" => $setting['ldap_password'],
            ]);
            return self::$init = true;
        } catch (ConfigurationException $e) {
            info($e->getMessage());
            return self::$init = false;
        }
    }

    /**
     * 获取
     * @param $username
     * @param $password
     * @return Model|null
     */
    public static function userFirst($username, $password): ?Model
    {
        if (!self::initConfig()) {
            return null;
        }
        try {
            return self::static()
                ->where([
                    'cn' => $username,
                    'userPassword' => $password
                ])->first();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * 登录
     * @param $username
     * @param $password
     * @param User|null $user
     * @return User|mixed|null
     */
    public static function userLogin($username, $password, $user = null)
    {
        if (!self::initConfig()) {
            return null;
        }
        $row = self::userFirst($username, $password);
        if (!$row) {
            return null;
        }
        if (empty($user)) {
            $user = User::reg($username, $password);
        }
        if ($user) {
            $userimg = $row->getPhoto();
            if ($userimg) {
                $path = "uploads/user/ldap/";
                $file = "{$path}{$user->userid}.jpeg";
                Base::makeDir(public_path($path));
                if (Base::saveContentImage(public_path($file), $userimg)) {
                    $user->userimg = $file;
                }
            }
            $user->nickname = $row->getDisplayName();
            $user->save();
        }
        return $user;
    }

    /**
     * 同步
     * @param User $user
     * @param $password
     * @return void
     */
    public static function userSync(User $user, $password)
    {
        if ($user->isLdap()) {
            return;
        }
        //
        if (!self::initConfig()) {
            return;
        }
        //
        if (self::isSyncLocal()) {
            $row = self::userFirst($user->email, $password);
            if ($row) {
                return;
            }
            try {
                $userimg = public_path($user->getRawOriginal('userimg'));
                if (file_exists($userimg)) {
                    $userimg = file_get_contents($userimg);
                } else {
                    $userimg = '';
                }
                self::static()->create([
                    'cn' => $user->email,
                    'gidNumber' => 0,
                    'homeDirectory' => '/home/ldap/dootask/' . env("APP_NAME"),
                    'sn' => $user->email,
                    'uid' => $user->email,
                    'uidNumber' => $user->userid,
                    'userPassword' => $password,
                    'displayName' => $user->nickname,
                    'jpegPhoto' => $userimg,
                ]);
                $user->identity = Base::arrayImplode(array_merge(array_diff($user->identity, ['ldap']), ['ldap']));
                $user->save();
            } catch (LdapRecordException $e) {
                info("[LDAP] sync fail: " . $e->getMessage());
            }
        }
    }

    /**
     * 更新
     * @param $username
     * @param $array
     * @return void
     */
    public static function userUpdate($username, $array)
    {
        if (empty($array)) {
            return;
        }
        if (!self::initConfig()) {
            return;
        }
        try {
            $row = self::static()
                ->where([
                    'cn' => $username,
                ])->first();
            $row?->update($array);
        } catch (\Exception $e) {
            info("[LDAP] update fail: " . $e->getMessage());
        }
    }

    /**
     * 删除
     * @param $username
     * @return void
     */
    public static function userDelete($username)
    {
        if (!self::initConfig()) {
            return;
        }
        try {
            $row = self::static()
                ->where([
                    'cn' => $username,
                ])->first();
            $row?->delete();
        } catch (\Exception $e) {
            info("[LDAP] delete fail: " . $e->getMessage());
        }
    }
}
