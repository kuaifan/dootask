<?php

namespace App\Http\Controllers\Api;

use Adldap\AdldapException;
use Adldap\AdldapInterface;
use App\Models\User;
use App\Module\Base;
use Arr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Request;

/**
 * @apiDefine ldap
 *
 * LDAP认证
 */
class LdapController extends AbstractController
{

    /**
     * @var AdldapInterface
     */
    private AdldapInterface $ldap;

    public function __construct(AdldapInterface $ldap) {
        $this->ldap = $ldap;
    }

    /**
     * @throws AdldapException
     * @api {get} api/ldap/login          01. 登录
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login
     *
     * @apiParam {String} email          邮箱
     * @apiParam {String} password       密码
     * @apiParam {String} [code]         登录验证码
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据（同"获取我的信息"接口）
     */
    public function login(): array {
        $username = trim(Request::input("username"));
        $password = trim(Request::input("password"));

        $provider = $this->ldap->getDefaultProvider();

        $unauthorized = function ($msg) {
            return Base::retError($msg);
        };

        // ldap唯一属性
        $ldap_unique_name = config("ldap_auth.unique_name", "mail");

        try {
            $res = $provider->search()
                ->whereEquals($ldap_unique_name, $username)
                // 超过2个都忽略
                ->limit(2)
                ->get();

            $count = count($res, COUNT_NORMAL);

            if ( $count == 0 ) {
                Log::warning("[ldap]: '$username' not exists");

                return $unauthorized("用户名或密码错误");
            } else if ( $count > 1 ) {
                return $unauthorized("该邮箱绑定了不指一个账号");
            } else {
                $first = Arr::get($res, 0);
            }

            $ldapDN = $first->getDn();

            Log::debug("[ldap]: '$username' ldap dn '$ldapDN'");

            if ( !$provider->auth()->attempt($ldapDN, $password) ) {
                Log::warning("[ldap]: '$username' password wrong");

                return $unauthorized("用户名或密码错误");
            }

            // ldap映射用户属性集合
            $ldapUser = array();

            //
            $attributes = config("ldap_auth.sync_attributes", array());

            // 将ldap属性映射为本地用户
            foreach ($attributes as $local_name => $ldap_name) {
                Log::debug("[ldap]: ldap attribute '${ldap_name}' map to '${local_name}'");

                $ldapUser[$local_name] = $first->getFirstAttribute($ldap_name);
            }

            if ( empty($ldapUser['email']) ) {
                return $unauthorized("你的账号未提供邮箱，请设置邮箱后再试");
            }

            if ( empty($ldapUser['nickname']) ) {
                $ldapUser['nickname'] = $username;
            }

            $localUser = User::whereEmail($ldapUser['email'])->first();

            if ( empty($localUser) ) {// 创建本地账号
                $localUser = $this->createUser($ldapUser);
            }

            if ( in_array('disable', $localUser->identity) ) {
                Log::warning("[ldap]: user '$username' is disabled"); return $unauthorized('帐号已停用...');
            }

            //
            $array = [
                'login_num' => $localUser->login_num + 1,
                'last_ip' => Base::getIp(),
                'last_at' => Carbon::now(),
                'line_ip' => Base::getIp(),
                'line_at' => Carbon::now(),
            ];

            $localUser->updateInstance($array);
            $localUser->save();
            User::token($localUser);
            Log::info("[ldap]: '$username' login success");
            return Base::retSuccess("登录成功", $localUser);
        } catch (AdldapException $e){
            Log::error($e); return Base::retError("认证失败");
        }
    }

    private function createUser($user): User
    {
        $encrypt = Base::generatePassword(6);
        $password = $user['password'];

        if ( empty($password) ) {
            $password = Base::generatePassword(16);
        }

        $attributes = [
            'encrypt' => $encrypt,
            'email' => $user['email'],
            'password' => Base::md52($password, $encrypt),
            'created_ip' => Base::getIp(),
            'nickname' => $user['nickname']
        ];

        $storeUser = User::createInstance($attributes);
        $storeUser->save();
        User::AZUpdate($storeUser->userid);
        return $storeUser->find($storeUser->userid);
    }
}
