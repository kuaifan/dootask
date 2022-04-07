<?php

namespace App\Models;


use App\Exceptions\ApiException;
use App\Module\Base;
use Cache;
use Carbon\Carbon;

/**
 * App\Models\User
 *
 * @property int $userid
 * @property array $identity 身份
 * @property string|null $az A-Z
 * @property string|null $email 邮箱
 * @property string $nickname 昵称
 * @property string|null $profession 职位/职称
 * @property string $userimg 头像
 * @property string|null $encrypt
 * @property string|null $password 登录密码
 * @property int|null $changepass 登录需要修改密码
 * @property int|null $login_num 累计登录次数
 * @property string|null $last_ip 最后登录IP
 * @property string|null $last_at 最后登录时间
 * @property string|null $line_ip 最后在线IP（接口）
 * @property string|null $line_at 最后在线时间（接口）
 * @property int|null $task_dialog_id 最后打开的任务会话ID
 * @property string|null $created_ip 注册IP
 * @property string|null $disable_at 禁用时间
 * @property int|null $email_verity 邮箱是否已验证
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereChangepass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDisableAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEncrypt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLoginNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfession($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTaskDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserimg($value)
 * @mixin \Eloquent
 */
class User extends AbstractModel
{
    protected $primaryKey = 'userid';

    protected $hidden = [
        'disable_at',
        'updated_at',
    ];

    /**
     * 更新数据校验
     * @param array $param
     */
    public function updateInstance(array $param)
    {
        parent::updateInstance($param);
        //
        if (isset($param['line_at']) && $this->userid) {
            Cache::put("User::online:" . $this->userid, time(), Carbon::now()->addSeconds(30));
        }
    }

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
        if ($value) {
            return Base::fillUrl($value);
        }
        $name = ($this->userid - 1) % 21 + 1;
        return url("images/avatar/default_{$name}.png");
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

    /**
     * 是否在线
     * @return bool
     */
    public function getOnlineStatus()
    {
        $online = intval(Cache::get("User::online:" . $this->userid, 0));
        if ($online) {
            return true;
        }
        return WebSocket::whereUserid($this->userid)->exists();
    }

    /**
     * 判断是否管理员
     */
    public function isAdmin()
    {
        $this->identity('admin');
    }

    /**
     * 判断用户权限（身份）
     * @param $identity
     */
    public function identity($identity)
    {
        if (!in_array($identity, $this->identity)) {
            throw new ApiException('权限不足');
        }
    }

    /**
     * 检查环境是否允许
     * @param null $onlyUserid  仅指定会员
     */
    public function checkSystem($onlyUserid = null)
    {
        if ($onlyUserid && $onlyUserid != $this->userid) {
            return;
        }
        if (env("PASSWORD_ADMIN") == 'disabled') {
            if ($this->userid == 1) {
                throw new ApiException('当前环境禁止此操作');
            }
        }
        if (env("PASSWORD_OWNER") == 'disabled') {
            throw new ApiException('当前环境禁止此操作');
        }
    }

    /** ***************************************************************************************** */
    /** ***************************************************************************************** */
    /** ***************************************************************************************** */

    /**
     * 注册会员
     * @param $email
     * @param $password
     * @param array $other
     * @return self
     */
    public static function reg($email, $password, $other = [])
    {
        //邮箱
        if (!Base::isEmail($email)) {
            throw new ApiException('请输入正确的邮箱地址');
        }
        if (User::email2userid($email) > 0) {
            $isRegVerify = Base::settingFind('emailSetting', 'reg_verify') === 'open';
            $user = self::whereUserid(User::email2userid($email))->first();
            if ($isRegVerify && $user->email_verity === 0) {
                UserEmailVerification::userEmailSend($user);
                throw new ApiException('您的账号已注册过，请验证邮箱', ['code' => 'email']);
            }
            throw new ApiException('邮箱地址已存在');
        }
        //密码
        self::passwordPolicy($password);
        //开始注册
        $encrypt = Base::generatePassword(6);
        $inArray = [
            'encrypt' => $encrypt,
            'email' => $email,
            'password' => Base::md52($password, $encrypt),
            'created_ip' => Base::getIp(),
        ];
        if ($other) {
            $inArray = array_merge($inArray, $other);
        }
        $user = User::createInstance($inArray);
        $user->save();
        User::AZUpdate($user->userid);
        return $user->find($user->userid);
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
     * 获取我的ID
     * @return int
     */
    public static function userid()
    {
        $user = self::authInfo();
        if (!$user) {
            return 0;
        }
        return $user->userid;
    }

    /**
     * 获取我的昵称
     * @return string
     */
    public static function nickname()
    {
        $user = self::authInfo();
        if (!$user) {
            return '';
        }
        return $user->nickname;
    }

    /**
     * 用户身份认证（获取用户信息）
     * @param null $identity 判断身份
     * @return self
     */
    public static function auth($identity = null)
    {
        $user = self::authInfo();
        if (!$user) {
            $authorization = Base::getToken();
            if ($authorization) {
                throw new ApiException('身份已失效,请重新登录', [], -1);
            } else {
                throw new ApiException('请登录后继续...', [], -1);
            }
        }
        if (in_array('disable', $user->identity)) {
            throw new ApiException('帐号已停用...', [], -1);
        }
        if ($identity) {
            $user->identity($identity);
        }
        return $user;
    }

    /**
     * 用户身份认证（获取用户信息）
     * @return self|false
     */
    private static function authInfo()
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
     * 生成token
     * @param self $userinfo
     * @return string
     */
    public static function token($userinfo)
    {
        $userinfo->token = base64_encode($userinfo->userid . '#$' . $userinfo->email . '#$' . $userinfo->encrypt . '#$' . time() . '#$' . Base::generatePassword(6));
        unset($userinfo->encrypt);
        unset($userinfo->password);
        return $userinfo->token;
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
     * userid 获取 基础信息
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
        $fields = ['userid', 'email', 'nickname', 'profession', 'userimg'];
        $userInfo = self::whereUserid($userid)->select($fields)->first();
        if ($userInfo) {
            $userInfo->online = $userInfo->getOnlineStatus();
        }
        return $_A["__static_userid2basic_" . $userid] = ($userInfo ?: []);
    }


    /**
     * userid 获取 昵称
     * @param $userid
     * @return string
     */
    public static function userid2nickname($userid)
    {
        $basic = self::userid2basic($userid);
        return $basic ? $basic->nickname : '';
    }

    /**
     * 更新首字母
     * @param $userid
     */
    public static function AZUpdate($userid)
    {
        $row = self::whereUserid($userid)->first();
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
        $login_code = Base::settingFind('system', 'login_code');
        switch ($login_code) {
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

    /**
     * 检测密码策略是否符合
     * @param $password
     * @return void
     */
    public static function passwordPolicy($password)
    {
        if (strlen($password) < 6) {
            throw new ApiException('密码设置不能小于6位数');
        }
        if (strlen($password) > 32) {
            throw new ApiException('密码最多只能设置32位数');
        }
        // 复杂密码
        $password_policy = Base::settingFind('system', 'password_policy');
        if ($password_policy == 'complex') {
            if (preg_match("/^[0-9]+$/", $password)) {
                throw new ApiException('密码不能全是数字，请包含数字，字母大小写或者特殊字符');
            }
            if (preg_match("/^[a-zA-Z]+$/", $password)) {
                throw new ApiException('密码不能全是字母，请包含数字，字母大小写或者特殊字符');
            }
            if (preg_match("/^[0-9A-Z]+$/", $password)) {
                throw new ApiException('密码不能全是数字+大写字母，密码包含数字，字母大小写或者特殊字符');
            }
            if (preg_match("/^[0-9a-z]+$/", $password)) {
                throw new ApiException('密码不能全是数字+小写字母，密码包含数字，字母大小写或者特殊字符');
            }
        }
    }
}
