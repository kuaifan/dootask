<?php

namespace App\Models;


use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;
use Cache;
use Carbon\Carbon;
use Request;

/**
 * App\Models\User
 *
 * @property int $userid
 * @property array $identity
 * @property array $department
 * @property string|null $az A-Z
 * @property string|null $pinyin 拼音（主要用于搜索）
 * @property string|null $email
 * @property string|null $tel 联系电话
 * @property string $nickname
 * @property string|null $profession
 * @property string $userimg
 * @property string|null $encrypt
 * @property string|null $password 登录密码
 * @property int|null $changepass 登录需要修改密码
 * @property int|null $login_num 累计登录次数
 * @property string|null $last_ip 最后登录IP
 * @property \Illuminate\Support\Carbon|null $last_at 最后登录时间
 * @property string|null $line_ip 最后在线IP（接口）
 * @property \Illuminate\Support\Carbon|null $line_at 最后在线时间（接口）
 * @property int|null $task_dialog_id 最后打开的任务会话ID
 * @property string|null $created_ip 注册IP
 * @property \Illuminate\Support\Carbon|null $disable_at
 * @property int|null $email_verity 邮箱是否已验证
 * @property int|null $bot 是否机器人
 * @property string|null $lang 语言首选项
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereChangepass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDisableAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEncrypt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLineIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLoginNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePinyin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfession($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTaskDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserimg($value)
 * @mixin \Eloquent
 */
class User extends AbstractModel
{
    protected $primaryKey = 'userid';

    protected $hidden = [
        'updated_at',
    ];

    // 默认头像类型：auto自动生成，system系统默认
    public static $defaultAvatarMode = 'auto';

    // 基本信息的字段
    public static $basicField = ['userid', 'email', 'nickname', 'profession', 'department', 'userimg', 'bot', 'az', 'pinyin', 'line_at', 'disable_at'];

    /**
     * 昵称
     * @param $value
     * @return string
     */
    public function getNicknameAttribute($value)
    {
        if ($value) {
            if (UserBot::isSystemBot($this->email)) {
                return Doo::translate($value);
            }
            return $value;
        }
        return Base::formatName($this->email);
    }

    /**
     * 头像地址
     * @param $value
     * @return string
     */
    public function getUserimgAttribute($value)
    {
        return self::getAvatar($this->userid, $value, $this->email, $this->nickname);
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
     * 部门
     * @param $value
     * @return array
     */
    public function getDepartmentAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_filter(is_array($value) ? $value : Base::explodeInt($value));
    }

    /**
     * 获取所属部门名称
     * @return string
     */
    public function getDepartmentName()
    {
        if (empty($this->department)) {
            return "";
        }
        $key = "UserDepartment::" . md5(Cache::get("UserDepartment::rand") . '-' . implode(',' , $this->department));
        $list = Cache::remember($key, now()->addMonth(), function() {
            $list = UserDepartment::select(['id', 'owner_userid', 'name'])->whereIn('id', $this->department)->take(10)->get();
            return $list->toArray();
        });
        $array = [];
        foreach ($list as $item) {
            $array[] = $item['name'] . ($item['owner_userid'] === $this->userid ? ' (M)' : '');
        }
        return implode(', ', $array);
    }

    /**
     * 判断是否为部门负责人
     */
    public function isDepartmentOwner()
    {
        return UserDepartment::where('owner_userid', $this->userid)->exists();
    }


    /**
     * 获取机器人所有者
     * @return int|mixed
     */
    public function getBotOwner()
    {
        if (!$this->bot) {
            return 0;
        }
        $key = "userBotOwner::" . $this->userid;
        return Cache::remember($key, now()->addMonth(), function() {
            return intval(UserBot::whereBotId($this->userid)->value('userid')) ?: $this->userid;
        });
    }

    /**
     * 是否在线
     * @return bool
     */
    public function getOnlineStatus()
    {
        $online = $this->bot || Cache::get("User::online:" . $this->userid) === "on";
        if ($online) {
            return true;
        }
        return WebSocket::whereUserid($this->userid)->exists();
    }

    /**
     * 返回是否LDAP用户
     * @return bool
     */
    public function isLdap()
    {
        return in_array('ldap', $this->identity);
    }

    /**
     * 返回是否临时帐号
     * @return bool
     */
    public function isTemp()
    {
        return in_array('temp', $this->identity);
    }

    /**
     * 返回是否禁用帐号（离职）
     * @return bool
     */
    public function isDisable()
    {
        return in_array('disable', $this->identity);
    }

    /**
     * 返回是否管理员
     * @return bool
     */
    public function isAdmin()
    {
        return in_array('admin', $this->identity);
    }

    /**
     * 判断是否管理员
     */
    public function checkAdmin()
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

    /**
     * 删除会员
     * @param $reason
     * @return bool|null
     */
    public function deleteUser($reason)
    {
        return AbstractModel::transaction(function () use ($reason) {
            // 删除原因
            $userDelete = UserDelete::createInstance([
                'operator' => User::userid(),
                'userid' => $this->userid,
                'email' => $this->email,
                'reason' => $reason,
                'cache' => array_merge($this->getRawOriginal(), [
                    'department_name' => $this->getDepartmentName()
                ])
            ]);
            $userDelete->save();
            // 删除未读
            WebSocketDialogMsgRead::whereUserid($this->userid)->delete();
            // 删除待办
            WebSocketDialogMsgTodo::whereUserid($this->userid)->delete();
            // 删除邮箱验证记录
            UserEmailVerification::whereEmail($this->email)->delete();
            //
            return $this->delete();
        });
    }

    /**
     * 检查发送聊天内容前必须设置昵称、电话
     * @return void
     */
    public function checkChatInformation()
    {
        if ($this->bot) {
            return;
        }
        $chatInformation = Base::settingFind('system', 'chat_information');
        if ($chatInformation == 'required') {
            if (empty($this->getRawOriginal('nickname'))) {
                throw new ApiException('请设置昵称', [], -2);
            }
            if (empty($this->getRawOriginal('tel'))) {
                throw new ApiException('请设置联系电话', [], -3);
            }
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
        // 邮箱
        if (!Base::isEmail($email)) {
            throw new ApiException('请输入正确的邮箱地址');
        }
        $user = self::whereEmail($email)->first();
        if ($user) {
            $isRegVerify = Base::settingFind('emailSetting', 'reg_verify') === 'open';
            if ($isRegVerify && $user->email_verity === 0) {
                UserEmailVerification::userEmailSend($user);
                throw new ApiException('您的帐号已注册过，请验证邮箱', ['code' => 'email']);
            }
            throw new ApiException('邮箱地址已存在');
        }
        // 密码
        self::passwordPolicy($password);
        // 开始注册
        $user = Doo::userCreate($email, $password);
        if ($other) {
            $user->updateInstance($other);
        }
        $user->az = Base::getFirstCharter($user->nickname);
        $user->pinyin = Base::cn2pinyin($user->nickname);
        $user->created_ip = Base::getIp();
        if ($user->save()) {
            $setting = Base::setting('system');
            $reg_identity = $setting['reg_identity'] ?: 'normal';
            $all_group_autoin = $setting['all_group_autoin'] ?: 'yes';
            // 注册临时身份
            if ($reg_identity === 'temp') {
                $user->identity = Base::arrayImplode(array_merge(array_diff($user->identity, ['temp']), ['temp']));
                $user->save();
            }
            // 加入全员群组
            if ($all_group_autoin === 'yes') {
                $dialog = WebSocketDialog::whereGroupType('all')->orderByDesc('id')->first();
                $dialog?->joinGroup($user->userid, 0);
            }
        }
        return $user->find($user->userid);
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
            if (Base::token()) {
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
        if (Doo::userId() > 0
            && !Doo::userExpired()
            && $user = self::whereUserid(Doo::userId())->whereEmail(Doo::userEmail())->whereEncrypt(Doo::userEncrypt())->first()) {
            $upArray = [];
            if (Base::getIp() && $user->line_ip != Base::getIp()) {
                $upArray['line_ip'] = Base::getIp();
            }
            if (Carbon::parse($user->line_at)->addSeconds(30)->lt(Carbon::now())) {
                $upArray['line_at'] = Carbon::now();
            }
            if (empty($user->lang) || Request::hasHeader('language')) {
                $lang = Request::header('language');
                if (Doo::checkLanguage($lang) && $user->lang != $lang) {
                    $upArray['lang'] = $lang;
                }
            }
            if ($upArray) {
                $user->updateInstance($upArray);
                $user->save();
            }
            return $_A["__static_auth"] = $user;
        }
        return $_A["__static_auth"] = false;
    }

    /**
     * 生成 token
     * @param self $userinfo
     * @param bool $refresh  获取新的token
     * @return string
     */
    public static function generateToken($userinfo, $refresh = false)
    {
        if (!$refresh) {
            if (Doo::userId() != $userinfo->userid
                || Doo::userEmail() != $userinfo->email
                || Doo::userEncrypt() != $userinfo->encrypt) {
                $refresh = true;
            }
        }
        if ($refresh) {
            $days = $userinfo->bot ? 0 : max(1, intval(Base::settingFind('system', 'token_valid_days', 30)));
            $token = Doo::tokenEncode($userinfo->userid, $userinfo->email, $userinfo->encrypt, $days);
        } else {
            $token = Doo::userToken();
        }
        unset($userinfo->encrypt);
        unset($userinfo->password);
        return $userinfo->token = $token;
    }

    /**
     * userid 获取 基础信息
     * @param int $userid 会员ID
     * @return self
     */
    public static function userid2basic($userid, $addField = [])
    {
        global $_A;
        if (empty($userid)) {
            return null;
        }
        $userid = intval($userid);
        if (isset($_A["__static_userid2basic_" . $userid])) {
            return $_A["__static_userid2basic_" . $userid];
        }
        $userInfo = self::whereUserid($userid)->select(array_merge(User::$basicField, $addField))->first();
        if ($userInfo) {
            $userInfo->online = $userInfo->getOnlineStatus();
            $userInfo->department_name = $userInfo->getDepartmentName();
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
        return self::userid2basic($userid)?->nickname ?: '';
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
     * 临时帐号别名
     * @return mixed|string
     */
    public static function tempAccountAlias()
    {
        $alias = Base::settingFind('system', 'temp_account_alias');
        return $alias ?: Doo::translate("临时帐号");
    }

    /**
     * 获取头像
     * @param $userid
     * @param $userimg
     * @param $email
     * @param $nickname
     * @return string
     */
    public static function getAvatar($userid, $userimg, $email, $nickname)
    {
        // 自定义头像
        if ($userimg && !str_contains($userimg, 'avatar/')) {
            return Base::fillUrl($userimg);
        }
        // 机器人头像
        switch ($email) {
            case 'system-msg@bot.system':
                return url("images/avatar/default_system.png");
            case 'task-alert@bot.system':
                return url("images/avatar/default_task.png");
            case 'check-in@bot.system':
                return url("images/avatar/default_checkin.png");
            case 'anon-msg@bot.system':
                return url("images/avatar/default_anon.png");
            case 'approval-alert@bot.system':
                return url("images/avatar/default_approval.png");
            case 'okr-alert@bot.system':
                return url("images/avatar/default_okr.png");
            case 'ai-openai@bot.system':
                return url("images/avatar/default_openai.png");
            case 'ai-claude@bot.system':
                return url("images/avatar/default_claude.png");
            case 'ai-gemini@bot.system':
                return url("images/avatar/default_gemini.png");
            case 'ai-zhipu@bot.system':
                return url("images/avatar/default_zhipu.png");
            case 'bot-manager@bot.system':
                return url("images/avatar/default_bot.png");
            case 'meeting-alert@bot.system':
                return url("images/avatar/default_meeting.png");
            }
        // 生成文字头像
        if (self::$defaultAvatarMode === 'auto') {
            return url("avatar/" . urlencode($nickname) . ".png");
        }
        // 系统默认头像
        $name = ($userid - 1) % 21 + 1;
        return url("images/avatar/default_{$name}.png");
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

    /**
     * 获取机器人或创建
     * @param $key
     * @param $update
     * @param $userid
     * @return self|null
     */
    public static function botGetOrCreate($key, $update = [], $userid = 0)
    {
        $email = "{$key}@bot.system";
        $botUser = self::whereEmail($email)->first();
        if (empty($botUser)) {
            $botUser = Doo::userCreate($email, Base::generatePassword(32));
            if (empty($botUser)) {
                return null;
            }
            $botUser->updateInstance([
                'created_ip' => Base::getIp(),
            ]);
            $botUser->save();
            if ($userid > 0) {
                UserBot::createInstance([
                    'userid' => $userid,
                    'bot_id' => $botUser->userid,
                ])->save();
            }
            //
            if (empty($update['nickname'])) {
                $update['nickname'] = UserBot::systemBotName($email);
            }
        }
        if ($update) {
            $botUser->updateInstance($update);
            if (isset($update['nickname'])) {
                $botUser->az = Base::getFirstCharter($botUser->nickname);
                $botUser->pinyin = Base::cn2pinyin($botUser->nickname);
            }
            $botUser->save();
        }
        return $botUser;
    }
}
