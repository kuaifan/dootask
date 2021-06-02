<?php

namespace App\Models;


use App\Module\Base;
use Cache;
use Carbon\Carbon;

/**
 * Class User
 *
 * @package App\Models
 * @property int $userid
 * @property array $identity 身份
 * @property string|null $az A-Z
 * @property string|null $email 邮箱
 * @property string $nickname 昵称
 * @property string|null $userimg 头像
 * @property string|null $encrypt
 * @property string|null $userpass 登录密码
 * @property int|null $login_num 累计登录次数
 * @property int|null $changepass 登录需要修改密码
 * @property string|null $last_ip 最后登录IP
 * @property \Illuminate\Support\Carbon|null $last_at 最后登录时间
 * @property string|null $line_ip 最后在线IP（接口）
 * @property \Illuminate\Support\Carbon|null $line_at 最后在线时间（接口）
 * @property string|null $created_ip 注册IP
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
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLoginNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserimg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserpass($value)
 * @mixin \Eloquent
 */
class User extends AbstractModel
{
    protected $primaryKey = 'userid';

    protected $hidden = [
        'encrypt',
        'userpass',
        'updated_at',
    ];

    /**
     * 昵称
     * @param $value
     * @return string
     */
    public function getNicknameAttribute($value)
    {
        return $value ?: Base::cardFormat($this->email);
    }

    /**
     * 头像地址
     * @param $value
     * @return string
     */
    public function getUserimgAttribute($value)
    {
        return $value ? Base::fillUrl($value) : url('images/other/avatar.png');
    }

    /**
     * 身份权限
     * @param $value
     * @return array
     */
    public function getIdentityAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_filter(is_array($value) ? $value : explode(",", trim($value, ",")));
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
            'created_ip' => Base::getIp(),
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
     * 邮箱获取userid
     * @param $email
     * @return int
     */
    public static function email2userid($email)
    {
        if (empty($email)) {
            return 0;
        }
        return intval(self::whereEmail($email)->value('userid'));
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
     * token获取会员邮箱
     * @return int
     */
    public static function token2email()
    {
        return self::authFind('email', Base::getToken());
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
        list($userid, $email, $encrypt, $timestamp) = explode("#$", base64_decode($token) . "#$#$#$#$");
        $array = [
            'userid' => intval($userid),
            'email' => $email ?: '',
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
                    $row = self::whereUserid($authInfo['userid'])->whereEmail($authInfo['email'])->whereEncrypt($authInfo['encrypt'])->first();
                    if ($row) {
                        $upArray = [];
                        if (Base::getIp() && $row->line_ip != Base::getIp()) {
                            $upArray['line_ip'] = Base::getIp();
                        }
                        if (Carbon::parse($row->line_at)->addSeconds(30)->lt(Carbon::now())) {
                            $upArray['line_at'] = Carbon::now();
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
        return base64_encode($userinfo->userid . '#$' . $userinfo->email . '#$' . $userinfo->encrypt . '#$' . time() . '#$' . Base::generatePassword(6));
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
    public static function userid2basic($userid)
    {
        global $_A;
        if (empty($userid)) {
            return null;
        }
        $userid = intval($userid);
        if (isset($_A["__static_userid2basic_" . $userid])) {
            return $_A["__static_userid2basic_" . $userid];
        }
        $fields = ['userid', 'email', 'nickname', 'userimg', 'line_at'];
        $userInfo = self::whereUserid($userid)->select($fields)->first();
        if ($userInfo) {
            $userInfo->line_at;
        }
        return $_A["__static_userid2basic_" . $userid] = ($userInfo ?: []);
    }

    /**
     * 更新首字母
     * @param $userid
     */
    public static function AZUpdate($userid)
    {
        $row = self::whereUserid($userid)->select(['email', 'nickname'])->first();
        if ($row) {
            $row->az = Base::getFirstCharter($row->nickname);
            $row->save();
        }
    }

    /**
     * 是否需要验证码
     * @param $email
     * @return array
     */
    public static function needCode($email)
    {
        $loginCode = Base::settingFind('system', 'loginCode');
        switch ($loginCode) {
            case 'open':
                return Base::retSuccess('need');

            case 'close':
                return Base::retError('no');

            default:
                if (Cache::get("code::" . $email) == 'need') {
                    return Base::retSuccess('need');
                } else {
                    return Base::retError('no');
                }
        }
    }
}
