<?php

namespace App\Ldap;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Module\Base;
use App\Services\RequestContext;
use LdapRecord\Configuration\ConfigurationException;
use LdapRecord\Container;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\Model;

class LdapUser extends Model
{
    /**
     * The object classes of the LDAP model.
     */
    public static array $objectClasses = [
        'person',
        'top',
    ];

    private static $emailAttrs = ['mail', 'cn', 'uid', 'userPrincipalName'];

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
     * 获取登录属性名
     * @return string
     */
    public static function getLoginAttr(): string
    {
        $attr = Base::settingFind('thirdAccessSetting', 'ldap_login_attr');
        return in_array($attr, ['cn', 'uid', 'mail', 'sAMAccountName', 'userPrincipalName']) ? $attr : 'cn';
    }

    /**
     * 初始化配置
     * @return bool
     */
    public static function initConfig()
    {
        if (RequestContext::has('ldap_init')) {
            return RequestContext::get('ldap_init');
        }
        //
        $setting = Base::setting('thirdAccessSetting');
        if ($setting['ldap_open'] !== 'open') {
            return RequestContext::save('ldap_init', false);
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
            return RequestContext::save('ldap_init', true);
        } catch (ConfigurationException $e) {
            info($e->getMessage());
            return RequestContext::save('ldap_init', false);
        }
    }

    /**
     * 通过管理员绑定搜索用户，然后用用户 DN 做 Bind 认证
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
            $loginAttr = self::getLoginAttr();
            $row = self::static()
                ->whereRaw($loginAttr, '=', $username)
                ->first();
            if (!$row) {
                return null;
            }
            $connection = Container::getDefaultConnection();
            if (!$connection->auth()->attempt($row->getDn(), $password)) {
                return null;
            }
            // Swoole 下连接共享，必须恢复管理员绑定
            $connection->auth()->attempt(
                $connection->getConfiguration()->get('username'),
                $connection->getConfiguration()->get('password')
            );
            return $row;
        } catch (\Exception $e) {
            info("[LDAP] auth fail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 通过邮箱查找 LDAP 用户
     * @param $email
     * @return Model|null
     */
    public static function findByEmail($email): ?Model
    {
        if (!self::initConfig()) {
            return null;
        }
        try {
            foreach (self::$emailAttrs as $attr) {
                $row = self::static()->whereRaw($attr, '=', $email)->first();
                if ($row) {
                    return $row;
                }
            }
            return null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * 获取用户的邮箱（从 LDAP 记录中提取）
     * @param Model $row
     * @return string|null
     */
    public static function getUserEmail(Model $row): ?string
    {
        foreach (self::$emailAttrs as $attr) {
            $val = $row->getFirstAttribute($attr);
            if ($val && Base::isEmail($val)) {
                return $val;
            }
        }
        return null;
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
            $email = self::getUserEmail($row);
            if (empty($email)) {
                throw new ApiException('LDAP 用户缺少邮箱属性，请联系管理员配置');
            }
            $user = User::whereEmail($email)->first();
            if (empty($user)) {
                // LDAP 用户通过 LDAP 认证，本地密码用随机值以满足密码策略
                $localPassword = Base::generatePassword(16) . 'Aa1!';
                $user = User::reg($email, $localPassword);
            } elseif (!$user->isLdap()) {
                info("[LDAP] merged with existing local account: userid={$user->userid}, email={$email}");
            }
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
            $row = self::findByEmail($user->email);
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
                $attrs = [
                    'cn' => $user->email,
                    'sn' => $user->email,
                    'uid' => $user->email,
                    'userPassword' => $password,
                    'displayName' => $user->nickname,
                    'mail' => $user->email,
                ];
                if ($userimg) {
                    $attrs['jpegPhoto'] = $userimg;
                }
                self::static()->create($attrs);
                $user->identity = Base::arrayImplode(array_merge(array_diff($user->identity, ['ldap']), ['ldap']));
                $user->save();
            } catch (LdapRecordException $e) {
                info("[LDAP] sync fail: " . $e->getMessage());
            }
        }
    }

    /**
     * 更新
     * @param $email
     * @param $array
     * @return void
     */
    public static function userUpdate($email, $array)
    {
        if (empty($array)) {
            return;
        }
        if (!self::initConfig()) {
            return;
        }
        try {
            $row = self::findByEmail($email);
            $row?->update($array);
        } catch (\Exception $e) {
            info("[LDAP] update fail: " . $e->getMessage());
        }
    }

    /**
     * 删除
     * @param $email
     * @return void
     */
    public static function userDelete($email)
    {
        if (!self::initConfig()) {
            return;
        }
        try {
            $row = self::findByEmail($email);
            $row?->delete();
        } catch (\Exception $e) {
            info("[LDAP] delete fail: " . $e->getMessage());
        }
    }
}
