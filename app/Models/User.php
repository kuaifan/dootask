<?php

namespace App\Models;


use App\Module\Base;
use Cache;

/**
 * Class User
 *
 * @package App\Models
 * @property int $userid
 * @property array $identity 身份
 * @property string|null $az A-Z
 * @property string|null $email 邮箱
 * @property string|null $username 用户名
 * @property string $nickname 昵称
 * @property string|null $userimg 头像
 * @property string|null $encrypt
 * @property string|null $userpass 登录密码
 * @property int|null $loginnum 累计登录次数
 * @property int|null $changepass 登录需要修改密码
 * @property string|null $lastip 最后登录IP
 * @property int|null $lastdate 最后登录时间
 * @property string|null $lineip 最后在线IP（接口）
 * @property int|null $linedate 最后在线时间（接口）
 * @property string|null $regip 注册IP
 * @property int|null $regdate 注册时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $usering
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereChangepass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEncrypt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLinedate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLoginnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRegdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRegip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserimg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserpass($value)
 * @mixin \Eloquent
 */
class User extends AbstractModel
{
    protected $primaryKey = 'userid';

    protected $hidden = [
        'encrypt',
        'userpass',
    ];

    /**
     * 昵称
     * @param $value
     * @return string
     */
    public function getNicknameAttribute($value)
    {
        if ($value) {
            return $value;
        }
        if ($this->username) {
            return $this->username;
        }
        return Base::getMiddle($this->email, null, "@");
    }

    /**
     * 头像地址
     * @param $value
     * @return string
     */
    public function getUseringAttribute($value)
    {
        return self::userimg($value);
    }

    /**
     * 身份权限
     * @param $value
     * @return array
     */
    public function getIdentityAttribute($value)
    {
        return is_array($value) ? $value : explode(",", trim($value, ","));
    }


    /** ***************************************************************************************** */
    /** ***************************************************************************************** */
    /** ***************************************************************************************** */

    /**
     * 注册会员
     * @param $email
     * @param $userpass
     * @param array $other
     * @return array
     */
    public static function reg($email, $userpass, $other = [])
    {
        //邮箱
        if (!Base::isMail($email)) {
            return Base::retError('请输入正确的邮箱地址！');
        }
        if (User::email2userid($email) > 0) {
            return Base::retError('邮箱地址已存在！');
        }
        //密码
        if (strlen($userpass) < 6) {
            return Base::retError(['密码设置不能小于%位数！', 6]);
        } elseif (strlen($userpass) > 32) {
            return Base::retError(['密码最多只能设置%位数！', 32]);
        }
        //开始注册
        $encrypt = Base::generatePassword(6);
        $inArray = [
            'encrypt' => $encrypt,
            'email' => $email,
            'userpass' => Base::md52($userpass, $encrypt),
            'regip' => Base::getIp(),
            'regdate' => time()
        ];
        if ($other) {
            $inArray = array_merge($inArray, $other);
        }
        $user = User::createInstance($inArray);
        $user->save();
        User::AZUpdate($user->userid);
        return Base::retSuccess('success', $user);
    }

    /**
     * userid获取用户名
     * @param $userid
     * @return mixed
     */
    public static function userid2username($userid)
    {
        if (empty($userid)) {
            return '';
        }
        return self::whereUserid(intval($userid))->value('username');
    }

    /**
     * 邮箱获取userid
     * @param $email
     * @return int
     */
    public static function email2userid($email)
    {
        if (empty($email)) {
            return 0;
        }
        return intval(self::whereUsername($email)->value('userid'));
    }

    /**
     * 用户名获取userid
     * @param $username
     * @return int
     */
    public static function username2userid($username)
    {
        if (empty($username)) {
            return 0;
        }
        return intval(self::whereUsername($username)->value('userid'));
    }

    /**
     * token获取会员userid
     * @return int
     */
    public static function token2userid()
    {
        return self::authFind('userid', Base::getToken());
    }

    /**
     * token获取会员账号
     * @return int
     */
    public static function token2username()
    {
        return self::authFind('username', Base::getToken());
    }

    /**
     * token获取encrypt
     * @return mixed|string
     */
    public static function token2encrypt()
    {
        return self::authFind('encrypt', Base::getToken());
    }

    /**
     * 获取token身份信息
     * @param $find
     * @param null $token
     * @return array|mixed|string
     */
    public static function authFind($find, $token = null)
    {
        if ($token === null) {
            $token = Base::getToken();
        }
        list($userid, $username, $encrypt, $timestamp) = explode("@", base64_decode($token) . "@@@@");
        $array = [
            'userid' => intval($userid),
            'username' => $username ?: '',
            'encrypt' => $encrypt ?: '',
            'timestamp' => intval($timestamp),
        ];
        if (isset($array[$find])) {
            return $array[$find];
        }
        if ($find == 'all') {
            return $array;
        }
        return '';
    }

    /**
     * 用户身份认证（获取用户信息）
     * @return array|mixed
     */
    public static function auth()
    {
        global $_A;
        if (isset($_A["__static_auth"])) {
            return $_A["__static_auth"];
        }
        $authorization = Base::getToken();
        if ($authorization) {
            $authInfo = self::authFind('all', $authorization);
            if ($authInfo['userid'] > 0) {
                $loginValid = floatval(Base::settingFind('system', 'loginValid')) ?: 720;
                $loginValid *= 3600;
                if ($authInfo['timestamp'] + $loginValid > time()) {
                    $row = self::whereUserid($authInfo['userid'])->whereUsername($authInfo['username'])->whereEncrypt($authInfo['encrypt'])->first();
                    if ($row) {
                        if ($row->token) {
                            $timestamp = self::authFind('timestamp', $row->token);
                            if ($timestamp + $loginValid > time()) {
                                $upArray = [];
                                if (Base::getIp() && $row->lineip != Base::getIp()) {
                                    $upArray['lineip'] = Base::getIp();
                                }
                                if ($row->linedate + 30 < time()) {
                                    $upArray['linedate'] = time();
                                }
                                if ($upArray) {
                                    $row->updateInstance($upArray);
                                    $row->save();
                                }
                                return $_A["__static_auth"] = $row;
                            }
                        }
                    }
                }
            }
        }
        return $_A["__static_auth"] = false;
    }

    /**
     * 用户身份认证（获取用户信息）
     * @return array
     */
    public static function authE()
    {
        $user = self::auth();
        if (!$user) {
            $authorization = Base::getToken();
            if ($authorization) {
                return Base::retError('身份已失效,请重新登录！', $user, -1);
            } else {
                return Base::retError('请登录后继续...', [], -1);
            }
        }
        return Base::retSuccess("auth", $user);
    }

    /**
     * 生成token
     * @param self $userinfo
     * @return string
     */
    public static function token($userinfo)
    {
        return base64_encode($userinfo->userid . '@' . $userinfo->username . '@' . $userinfo->encrypt . '@' . time() . '@' . Base::generatePassword(6));
    }

    /**
     * 判断用户权限（身份）
     * @param $identity
     * @return array
     */
    public static function identity($identity)
    {
        $user = self::auth();
        if (is_array($user->identity)
            && in_array($identity, $user->identity)) {
            return Base::retSuccess("success");
        }
        return Base::retError("权限不足！");
    }

    /**
     * 判断用户权限（身份）
     * @param $identity
     * @return bool
     */
    public static function identityCheck($identity)
    {
        if (is_array($identity)) {
            foreach ($identity as $id) {
                if (!Base::isError(self::identity($id)))
                    return true;
            }
            return false;
        }
        return Base::isSuccess(self::identity($identity));
    }

    /**
     * 判断用户权限（身份）
     * @param $identity
     * @param $userIdentity
     * @return bool
     */
    public static function identityRaw($identity, $userIdentity)
    {
        $userIdentity = is_array($userIdentity) ? $userIdentity : explode(",", trim($userIdentity, ","));
        return $identity && in_array($identity, $userIdentity);
    }

    /**
     * userid 获取 基本信息
     * @param int $userid 会员ID
     * @return self
     */
    public static function userid2basic(int $userid)
    {
        global $_A;
        if (empty($userid)) {
            return null;
        }
        if (isset($_A["__static_userid2basic_" . $userid])) {
            return $_A["__static_userid2basic_" . $userid];
        }
        $fields = ['userid', 'email', 'username', 'nickname', 'userimg'];
        $userInfo = self::whereUserid($userid)->select($fields)->first();
        return $_A["__static_userid2basic_" . $userid] = ($userInfo ?: []);
    }

    /**
     * username 获取 基本信息
     * @param string $username 用户名
     * @return self
     */
    public static function username2basic(string $username)
    {
        global $_A;
        if (empty($username)) {
            return null;
        }
        if (isset($_A["__static_username2basic_" . $username])) {
            return $_A["__static_username2basic_" . $username];
        }
        $fields = ['userid', 'email', 'username', 'nickname', 'userimg'];
        $userInfo = self::whereUsername($username)->select($fields)->first();
        return $_A["__static_username2basic_" . $username] = ($userInfo ?: []);
    }

    /**
     * email 获取 基本信息
     * @param string $email 邮箱地址
     * @return self
     */
    public static function email2basic(string $email)
    {
        global $_A;
        if (empty($email)) {
            return null;
        }
        if (isset($_A["__static_email2basic_" . $email])) {
            return $_A["__static_email2basic_" . $email];
        }
        $fields = ['userid', 'email', 'username', 'nickname', 'userimg'];
        $userInfo = self::whereEmail($email)->select($fields)->first();
        return $_A["__static_email2basic_" . $email] = ($userInfo ?: []);
    }

    /**
     * 用户头像，不存在时返回默认
     * @param string $var 头像地址 或 会员用户名
     * @return string
     */
    public static function userimg(string $var)
    {
        if (!Base::strExists($var, '.')) {
            if (empty($var)) {
                $var = "";
            } else {
                $userInfo = self::username2basic($var);
                if ($userInfo) {
                    $var = $userInfo->userimg;
                }
            }
        }
        return $var ? Base::fillUrl($var) : url('images/other/avatar.png');
    }

    /**
     * 更新首字母
     * @param $userid
     */
    public static function AZUpdate($userid)
    {
        $row = self::whereUserid($userid)->select(['email', 'username', 'nickname'])->first();
        if ($row) {
            $row->az = Base::getFirstCharter($row->nickname);
            $row->save();
        }
    }

    /**
     * 是否需要验证码
     * @param $username
     * @return array
     */
    public static function needCode($username)
    {
        $loginCode = Base::settingFind('system', 'loginCode');
        switch ($loginCode) {
            case 'open':
                return Base::retSuccess('need');

            case 'close':
                return Base::retError('no');

            default:
                if (Cache::get("code::" . $username) == 'need') {
                    return Base::retSuccess('need');
                } else {
                    return Base::retError('no');
                }
        }
    }
}
