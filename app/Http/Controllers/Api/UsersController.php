<?php

namespace App\Http\Controllers\Api;

use Arr;
use Cache;
use Captcha;
use Request;
use Carbon\Carbon;
use App\Module\Doo;
use App\Models\File;
use App\Models\User;
use App\Module\Base;
use App\Module\Timer;
use App\Ldap\LdapUser;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskFile;
use App\Models\UserBot;
use App\Models\WebSocket;
use App\Models\UmengAlias;
use App\Models\UserDelete;
use App\Models\UserDevice;
use App\Models\UserTransfer;
use App\Models\AbstractModel;
use App\Models\UserCheckinFace;
use App\Models\UserCheckinMac;
use App\Models\UserDepartment;
use App\Models\WebSocketDialog;
use App\Models\UserCheckinRecord;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Models\UserTaskBrowse;
use App\Models\UserFavorite;
use App\Models\UserRecentItem;
use App\Models\UserTag;
use App\Models\UserTagRecognition;
use App\Models\UserAppSort;
use Illuminate\Support\Facades\DB;
use App\Models\UserEmailVerification;
use App\Module\AgoraIO\AgoraTokenGenerator;
use Swoole\Coroutine;

/**
 * @apiDefine users
 *
 * 会员
 */
class UsersController extends AbstractController
{
    /**
     * @api {get} api/users/login 登录、注册
     *
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login
     *
     * @apiParam {String} type           类型
     * - login:登录（默认）
     * - reg:注册
     * @apiParam {String} email          邮箱
     * @apiParam {String} password       密码
     * @apiParam {String} [code]         登录验证码
     * @apiParam {String} [code_key]     验证码通过key验证
     * @apiParam {String} [invite]       注册邀请码
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据（同"获取我的信息"接口）
     */
    public function login()
    {
        $type = trim(Request::input('type'));
        $email = trim(Request::input('email'));
        $password = trim(Request::input('password'));
        $isRegVerify = Base::settingFind('emailSetting', 'reg_verify') === 'open';
        if ($type == 'reg') {
            if (mb_strlen($email) > 32 || mb_strlen($password) > 32) {
                return Base::retError('账号密码最多可输入32位字符');
            }
            $setting = Base::setting('system');
            if ($setting['reg'] == 'close') {
                return Base::retError('未开放注册');
            } elseif ($setting['reg'] == 'invite') {
                $invite = trim(Request::input('invite'));
                if (empty($invite) || $invite != $setting['reg_invite']) {
                    return Base::retError('请输入正确的邀请码');
                }
            }
            $user = User::reg($email, $password);
            if ($isRegVerify) {
                UserEmailVerification::userEmailSend($user);
                return Base::retError('注册成功，请验证邮箱后登录', ['code' => 'email']);
            }
        } else {
            if (mb_strlen($email) > 32 || mb_strlen($password) > 32) {
                return Base::retError('帐号或密码错误');
            }
            $needCode = !Base::isError(User::needCode($email));
            if ($needCode) {
                $code = trim(Request::input('code'));
                $codeKey = trim(Request::input('code_key'));
                if (empty($code)) {
                    return Base::retError('请输入验证码', ['code' => 'need']);
                }
                if ($codeKey) {
                    $check = Captcha::check_api($code, $codeKey);
                } else {
                    $check = Captcha::check($code);
                }
                if (!$check) {
                    return Base::retError('请输入正确的验证码', ['code' => 'need']);
                }
            }
            //
            $retError = function ($msg) use ($email) {
                Cache::forever("code::" . $email, "need");
                $needCode = !Base::isError(User::needCode($email));
                $needData = ['code' => $needCode ? 'need' : 'no'];
                return Base::retError($msg, $needData);
            };
            //
            $user = User::whereEmail($email)->first();
            $usePassword = true;
            if (LdapUser::isOpen()) {
                if (empty($user) || $user->isLdap()) {
                    $user = LdapUser::userLogin($email, $password, $user);
                    if ($user) {
                        $user->identity = Base::arrayImplode(array_merge(array_diff($user->identity, ['ldap']), ['ldap']));
                        $user->save();
                    }
                    $usePassword = false;
                }
            }
            if (empty($user)) {
                return $retError('帐号或密码错误');
            }
            if ($usePassword && $user->password != Doo::md5s($password, $user->encrypt)) {
                return $retError('帐号或密码错误');
            }
            //
            if ($user->isDisable()) {
                return $retError('帐号已停用...');
            }
            Cache::forget("code::" . $email);
            if ($isRegVerify && $user->email_verity === 0) {
                UserEmailVerification::userEmailSend($user);
                return Base::retError('您还没有验证邮箱，请先登录邮箱通过验证邮件验证邮箱', ['code' => 'email']);
            }
        }
        //
        $array = [
            'login_num' => $user->login_num + 1,
            'last_ip' => Base::getIp(),
            'last_at' => Carbon::now(),
            'line_ip' => Base::getIp(),
            'line_at' => Carbon::now(),
        ];
        $user->updateInstance($array);
        $user->save();
        User::generateToken($user);
        LdapUser::userSync($user, $password);
        //
        if (!Project::withTrashed()->whereUserid($user->userid)->wherePersonal(1)->exists()) {
            Project::createProject([
                'name' => "📝 " . Doo::translate('个人项目'),
                'desc' => Doo::translate('注册时系统自动创建项目，你可以自由删除。'),
                'personal' => 1,
            ], $user->userid);
        }
        //
        return Base::retSuccess($type == 'reg' ? "注册成功" : "登录成功", $user);
    }

    /**
     * @api {get} api/users/login/qrcode 二维码登录
     *
     * @apiDescription 通过二维码code登录 (或：是否登录成功)
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login__qrcode
     *
     * @apiParam {String} type          类型
     * - login: 登录（用于：app登录）
     * - status: 状态 (默认，用于：网页、客户端获取)
     * @apiParam {String} code          二维码 code
     *
     * @apiSuccess {Number} ret     返回状态码（1需要、0不需要）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function login__qrcode()
    {
        $type = trim(Request::input('type'));
        $code = trim(Request::input('code'));
        $key = "User::qrcode:" . $code;
        //
        if (strlen($code) < 32) {
            return Base::retError("参数错误");
        }
        //
        if ($type === 'login') {
            $user = User::auth();
            Cache::put($key, $user->userid, Carbon::now()->addSeconds(30));
            return Base::retSuccess("扫码成功");
        }
        //
        $userid = intval(Cache::get($key));
        if ($userid > 0 && $user = User::whereUserid($userid)->first()) {
            $array = [
                'login_num' => $user->login_num + 1,
                'last_ip' => Base::getIp(),
                'last_at' => Carbon::now(),
                'line_ip' => Base::getIp(),
                'line_at' => Carbon::now(),
            ];
            $user->updateInstance($array);
            $user->save();
            User::generateToken($user);
            return Base::retSuccess("success", $user);
        }
        //
        return Base::retError("No identity");
    }

    /**
     * @api {get} api/users/login/needcode 是否需要验证码
     *
     * @apiDescription 用于判断是否需要登录验证码
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login__needcode
     *
     * @apiParam {String} email       用户名
     *
     * @apiSuccess {Number} ret     返回状态码（1需要、0不需要）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function login__needcode()
    {
        return User::needCode(trim(Request::input('email')));
    }

    /**
     * @api {get} api/users/login/codeimg 验证码图片
     *
     * @apiDescription 用于判断是否需要登录验证码
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login__codeimg
     *
     * @apiSuccess {Image} data     返回数据（直接输出图片）
     */
    public function login__codeimg()
    {
        return Captcha::create();
    }

    /**
     * @api {get} api/users/login/codejson 验证码json
     *
     * @apiDescription 用于判断是否需要登录验证码
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login__codejson
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function login__codejson()
    {
        $captcha = Captcha::create('default', true);
        return Base::retSuccess('请求成功', $captcha);
    }

    /**
     * @api {get} api/users/logout 退出登录
     *
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName logout
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     */
    public function logout()
    {
        UserDevice::forget();
        return Base::retSuccess('退出成功');
    }

    /**
     * @api {get} api/users/token/expire 查询 token 过期时间
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName token__expire
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String|null} data.expired_at    token 过期时间（null 表示永久有效）
     * @apiSuccess {Number|null} data.remaining_seconds    距离过期剩余秒数（负值表示已过期）
     * @apiSuccess {Boolean} data.expired    token 是否已过期
     * @apiSuccess {String} data.server_time    当前服务器时间
     */
    public function token__expire()
    {
        User::auth();
        $expiredAt = Doo::userExpiredAt();
        $expired = Doo::userExpired();
        $expiredAtCarbon = $expiredAt ? Carbon::parse($expiredAt) : null;
        $data = [
            'expired_at' => $expiredAtCarbon?->toDateTimeString(),
            'remaining_seconds' => $expiredAtCarbon ? Carbon::now()->diffInSeconds($expiredAtCarbon, false) : null,
            'expired' => $expired,
            'server_time' => Carbon::now()->toDateTimeString(),
        ];
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/users/reg/needinvite 是否需要邀请码
     *
     * @apiDescription 用于判断注册是否需要邀请码
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName reg__needinvite
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function reg__needinvite()
    {
        return Base::retSuccess('success', [
            'need' => Base::settingFind('system', 'reg') == 'invite'
        ]);
    }

    /**
     * @api {get} api/users/info 获取我的信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName info
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} data:
    {
        "userid": 1,
        "identity": [ ],
        "department": [ ],
        "az": "",
        "email": "admin@admin.com",
        "nickname": "admin",
        "userimg": "",
        "login_num": 10,
        "changepass": 0,
        "last_ip": "127.0.0.1",
        "last_at": "2021-06-01 12:00:00",
        "line_ip": "127.0.0.1",
        "line_at": "2021-06-01 12:00:00",
        "created_ip": "",
    }
     */
    public function info()
    {
        $user = User::auth();
        //
        $refreshToken = false;
        if (in_array(Base::platform(), ['ios', 'android'])) {
            // 移动端token还剩7天到期时获取新的token
            $expiredAt = Doo::userExpiredAt();
            if ($expiredAt && Carbon::parse($expiredAt)->isBefore(Carbon::now()->addDays(7))) {
                $refreshToken = true;
            }
        }
        User::generateToken($user, $refreshToken);
        //
        $data = $user->toArray();
        $data['nickname_original'] = $user->getRawOriginal('nickname');
        $data['department_name'] = $user->getDepartmentName();
        $data['department_owner'] = UserDepartment::where('parent_id',0)->where('owner_userid', $user->userid)->exists(); // 适用默认部门下第1级负责人才能添加部门OKR
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/users/info/departments 获取我的部门列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName info__departments
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} data:
    [
        {
            "id": 1,
            "name": "部门1",
            "parent_id": 0,
            "owner_userid": 1
        },
     ]
     */
    public function info__departments()
    {
        $user = User::auth();

        // 获取部门列表
        $list = UserDepartment::select(['id', 'owner_userid', 'parent_id', 'name'])
            ->whereIn('id', $user->department)
            ->take(10)
            ->get()
            ->toArray();

        // 将 owner_userid 等于当前用户的部门排在前面
        usort($list, function($a, $b) use ($user) {
            if ($a['owner_userid'] == $user->userid && $b['owner_userid'] != $user->userid) {
                return -1;
            } elseif ($a['owner_userid'] != $user->userid && $b['owner_userid'] == $user->userid) {
                return 1;
            }
            return 0;
        });

        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/users/editdata 修改自己的资料
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName editdata
     *
     * @apiParam {Object} [userimg]             会员头像（地址）
     * @apiParam {String} [tel]                 电话
     * @apiParam {String} [nickname]            昵称
     * @apiParam {String} [profession]          职位/职称
     * @apiParam {String} [birthday]            生日（格式：YYYY-MM-DD）
     * @apiParam {String} [address]             地址
     * @apiParam {String} [introduction]        个人简介
     * @apiParam {String} [lang]                语言（比如：zh/en）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据（同"获取我的信息"接口）
     */
    public function editdata()
    {
        $user = User::auth();
        //
        $data = Request::all();
        $user->checkSystem(1);
        $upLdap = [];
        // 头像
        if (Arr::exists($data, 'userimg')) {
            $userimg = Request::input('userimg');
            $userimg = $userimg ? Base::unFillUrl(is_array($userimg) ? $userimg[0]['path'] : $userimg) : '';
            if (str_contains($userimg, 'avatar/')) {
                $userimg = '';
            }
            $user->userimg = $userimg;
            if (file_exists(public_path($userimg))) {
                $upLdap['jpegPhoto'] = file_get_contents(public_path($userimg));
            }
        }
        // 电话
        if (Arr::exists($data, 'tel')) {
            $tel = trim(Request::input('tel'));
            if (strlen($tel) < 6 || strlen($tel) > 20) {
                return Base::retError('联系电话长度错误');
            }
            if ($tel != $user->tel && User::whereTel($tel)->exists()) {
                return Base::retError('联系电话已存在');
            }
            $user->tel = $tel;
            $upLdap['mobile'] = $tel;
        }
        // 昵称
        if (Arr::exists($data, 'nickname')) {
            $nickname = trim(Request::input('nickname'));
            if ($nickname && mb_strlen($nickname) < 2) {
                return Base::retError('昵称不可以少于2个字');
            } elseif (mb_strlen($nickname) > 20) {
                return Base::retError('昵称最多只能设置20个字');
            } elseif ($nickname != $user->nickname) {
                $user->nickname = $nickname;
                $user->az = Base::getFirstCharter($nickname);
                $user->pinyin = Base::cn2pinyin($nickname);
                $upLdap['displayName'] = $nickname;
            }
        }
        // 职位/职称
        if (Arr::exists($data, 'profession')) {
            $profession = trim(Request::input('profession'));
            if ($profession && mb_strlen($profession) < 2) {
                return Base::retError('职位/职称不可以少于2个字');
            } elseif (mb_strlen($profession) > 20) {
                return Base::retError('职位/职称最多只能设置20个字');
            } else {
                $user->profession = $profession;
                $upLdap['employeeType'] = $profession;
            }
        }
        // 生日
        if (Arr::exists($data, 'birthday')) {
            $birthday = trim((string) Request::input('birthday'));
            if ($birthday === '') {
                $user->birthday = null;
            } else {
                try {
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
                        $birthdayDate = Carbon::createFromFormat('Y-m-d', $birthday);
                    } else {
                        $birthdayDate = Carbon::parse($birthday);
                    }
                } catch (\Exception $e) {
                    return Base::retError('生日格式错误');
                }
                $user->birthday = $birthdayDate->format('Y-m-d');
            }
        }
        // 地址
        if (Arr::exists($data, 'address')) {
            $address = trim((string) Request::input('address'));
            if (mb_strlen($address) > 100) {
                return Base::retError('地址最多只能设置100个字');
            }
            $user->address = $address ?: null;
        }
        // 个人简介
        if (Arr::exists($data, 'introduction')) {
            $introduction = trim((string) Request::input('introduction'));
            if (mb_strlen($introduction) > 500) {
                return Base::retError('个人简介最多只能设置500个字');
            }
            $user->introduction = $introduction ?: null;
        }
        // 语言
        if (Arr::exists($data, 'lang')) {
            $lang = trim(Request::input('lang'));
            if (!Doo::checkLanguage($lang)) {
                return Base::retError('语言错误');
            } else {
                $user->lang = $lang;
            }
        }
        //
        $user->save();
        User::generateToken($user);
        LdapUser::userUpdate($user->email, $upLdap);
        //
        return Base::retSuccess('修改成功', $user);
    }

    /**
     * @api {get} api/users/editpass 修改自己的密码
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName editpass
     *
     * @apiParam {String} oldpass           旧密码
     * @apiParam {String} newpass           新密码
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据（同"获取我的信息"接口）
     */
    public function editpass()
    {
        $user = User::auth();
        $user->checkSystem();
        //
        $oldpass = trim(Request::input('oldpass'));
        $newpass = trim(Request::input('newpass'));
        if ($oldpass == $newpass) {
            return Base::retError('新旧密码一致');
        }
        User::passwordPolicy($newpass);
        //
        $verify = User::whereUserid($user->userid)->wherePassword(Doo::md5s($oldpass, Doo::userEncrypt()))->count();
        if (empty($verify)) {
            return Base::retError('请填写正确的旧密码');
        }
        //
        $user->encrypt = Base::generatePassword(6);
        $user->password = Doo::md5s($newpass, $user->encrypt);
        $user->changepass = 0;
        $user->save();
        User::generateToken($user);
        LdapUser::userUpdate($user->email, ['userPassword' => $newpass]);
        return Base::retSuccess('修改成功', $user);
    }

    /**
     * @api {get} api/users/search 搜索会员列表
     *
     * @apiDescription 搜索会员列表
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName searchinfo
     *
     * @apiParam {Object} keys          搜索条件
     * - keys.key                           会员ID、昵称、拼音、邮箱关键字
     * - keys.disable                       0-排除离职（默认），1-仅离职，2-含离职
     * - keys.bot                           0-排除机器人（默认），1-仅机器人，2-含机器人
     * - keys.project_id                    在指定项目ID
     * - keys.no_project_id                 不在指定项目ID
     * - keys.dialog_id                     在指定对话ID
     * - keys.departments                   部门ID（多个用逗号分隔）
     * @apiParam {Object} sorts         排序方式
     * - sorts.az                           按字母：asc|desc
     * @apiParam {Number} updated_time  在这个时间戳之后更新的
     * @apiParam {Number} state         获取在线状态
     * - 0: 不获取（默认）
     * - 1: 获取会员在线状态，返回数据多一个online值
     * @apiParam {Number} [with_department]  是否返回部门信息
     * - 0: 不返回部门信息（默认）
     * - 1: 返回部门信息（返回数据多一个department_info字段），department_info={id, name, parent_id, owner_userid}
     *
     * @apiParam {Number} [take]        获取数量，10-100
     * @apiParam {Number} [page]        当前页，默认:1（赋值分页模式，take参数无效）
     * @apiParam {Number} [pagesize]    每页显示数量，默认:10，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function search()
    {
        $user = User::auth();
        //
        $columns = User::$basicField;
        $columns[] = 'created_at';
        $columns[] = 'identity';
        $builder = User::select($columns);
        //
        $keys = Request::input('keys');
        $sorts = Request::input('sorts');
        $updatedTime = intval(Request::input('updated_time'));
        $state = intval(Request::input('state', 0));
        $withDepartment = intval(Request::input('with_department', 0));
        $keys = is_array($keys) ? $keys : [];
        $sorts = is_array($sorts) ? $sorts : [];
        //
        if ($keys['key']) {
            if (str_contains($keys['key'], "@")) {
                $builder->where("email", "like", "%{$keys['key']}%");
            } elseif (Base::isNumber($keys['key'])) {
                $builder->where(function ($query) use ($keys) {
                    $query->where("userid", intval($keys['key']))
                        ->orWhere("nickname", "like", "%{$keys['key']}%")
                        ->orWhere("pinyin", "like", "%{$keys['key']}%")
                        ->orWhere("profession", "like", "%{$keys['key']}%");
                });
            } else {
                $builder->where(function($query) use ($keys) {
                    $query->where("nickname", "like", "%{$keys['key']}%")
                        ->orWhere("pinyin", "like", "%{$keys['key']}%")
                        ->orWhere("profession", "like", "%{$keys['key']}%");
                });
            }
        }
        if (intval($keys['disable']) == 0) {
            $builder->whereNull("disable_at");
        } elseif (intval($keys['disable']) == 1) {
            $builder->whereNotNull("disable_at");
        }
        if (intval($keys['bot']) == 0) {
            $builder->where("bot", 0);
        } elseif (intval($keys['bot']) == 1) {
            $builder->where("bot", 1);
        }
        if ($updatedTime > 0) {
            $builder->where("updated_at", ">=", Carbon::createFromTimestamp($updatedTime));
        }
        if (intval($keys['project_id']) > 0) {
            $builder->whereIn('userid', function ($query) use ($keys) {
                $query->select('userid')->from('project_users')->where('project_id', $keys['project_id']);
            });
        }
        if (intval($keys['no_project_id']) > 0) {
            $builder->whereNotIn('userid', function ($query) use ($keys) {
                $query->select('userid')->from('project_users')->where('project_id', $keys['no_project_id']);
            });
        }
        if (intval($keys['dialog_id']) > 0) {
            $builder->whereIn('userid', function ($query) use ($keys) {
                $query->select('userid')->from('web_socket_dialog_users')->where('dialog_id', $keys['dialog_id']);
            });
        }
        if ($keys['departments']) {
            if (!is_array($keys['departments'])) {
                $keys['departments'] = explode(",", $keys['departments']);
            }
            $builder->where(function($query) use ($keys) {
                foreach ($keys['departments'] AS $department) {
                    $query->orWhereRaw("FIND_IN_SET('{$department}', department)");
                }
            });
        }
        if (in_array($sorts['az'], ['asc', 'desc'])) {
            $builder->orderBy('az', $sorts['az']);
        } else {
            if (intval($keys['disable']) == 2) {
                $builder->orderBy('disable_at');
            }
            if (intval($keys['bot']) == 2) {
                $builder->orderBy('bot');
            }
        }
        //
        if (Request::exists('page')) {
            $list = $builder->orderBy('userid')->paginate(Base::getPaginate(100, 10));
        } else {
            $list = $builder->orderBy('userid')->take(Base::getPaginate(100, 10, 'take'))->get();
        }
        //
        $list->transform(function (User $userInfo) use ($user, $state, $withDepartment) {
            $tags = [];
            $dep = $userInfo->getDepartmentName();
            $dep = array_values(array_filter(explode(",", $dep), function($item) {
                return preg_match("/\(M\)$/", $item);
            }));
            if ($dep) {
                $tags[] = preg_replace("/\(M\)$/", "", trim($dep[0])) . Doo::translate("负责人");
            }
            if ($user->isAdmin()) {
                if ($userInfo->isAdmin()) {
                    $tags[] = Doo::translate("系统管理员");
                }
                if ($userInfo->isTemp()) {
                    $tags[] = User::tempAccountAlias(); // 临时帐号
                }
                if ($userInfo->userid > 3 && Carbon::parse($userInfo->created_at)->isAfter(Carbon::now()->subDays(30))) {
                    $tags[] = Doo::translate("新帐号");
                }
            }
            $userInfo->tags = $tags;
            //
            if ($state === 1) {
                $userInfo->online = $userInfo->getOnlineStatus();
            }
            if ($withDepartment) {
                $userInfo->department_info = UserDepartment::getDepartmentsByIds($userInfo->department);
            }
            return $userInfo;
        });
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/users/search/ai 获取AI机器人
     *
     * @apiDescription 搜索会员列表
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName search__ai
     *
     * @apiParam {String} type          AI 类型（比如：openai）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function search__ai()
    {
        User::auth();
        //
        $type = trim(Request::input('type'));
        $botName = "ai-{$type}";
        if (!UserBot::systemBotName($botName)) {
            return Base::retError('AI机器人不存在');
        }
        //
        $botUser = User::botGetOrCreate($botName);
        if (empty($botUser)) {
            return Base::retError('AI机器人不存在');
        }
        return Base::retSuccess('success', $botUser);
    }

    /**
     * @api {get} api/users/basic 获取指定会员基础信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName basic
     *
     * @apiParam {Number} userid          会员ID(多个格式：jsonArray，一次最多50个)
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function basic()
    {
        $sharekey = Request::header('sharekey');
        if (empty($sharekey) || !Meeting::getShareInfo($sharekey)) {
            User::auth();
        }
        //
        $userid = Request::input('userid');
        $array = Base::json2array($userid);
        if (empty($array)) {
            $array[] = $userid;
        }
        if (count($array) > 50) {
            return Base::retError('一次最多只能获取50条数据');
        }
        $retArray = [];
        foreach ($array AS $id) {
            $basic = User::userid2basic($id);
            if (empty($basic)) {
                $basic = UserDelete::userid2basic($id);
            }
            if ($basic) {
                $retArray[] = $basic;
            }
        }
        return Base::retSuccess('success', $retArray);
    }

    /**
     * @api {get} api/users/extra 获取会员扩展信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName extra
     *
     * @apiParam {Number} [userid]          会员ID（不传默认为当前用户）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function extra()
    {
        $user = User::auth();
        //
        $userid = intval(Request::input('userid'));
        if ($userid <= 0) {
            $userid = $user->userid;
        }
        if ($userid <= 0) {
            return Base::retError('会员不存在');
        }

        $user = User::query()
            ->select(['userid', 'birthday', 'address', 'introduction'])
            ->whereUserid($userid)
            ->first();

        $birthday = null;
        $address = null;
        $introduction = null;

        if ($user) {
            $birthday = $user->birthday;
            $address = $user->address;
            $introduction = $user->introduction;
        } else {
            $deleted = UserDelete::whereUserid($userid)->first();
            if (empty($deleted) || empty($deleted->cache)) {
                return Base::retError('会员不存在');
            }
            $birthday = $deleted->cache['birthday'] ?? null;
            $address = $deleted->cache['address'] ?? null;
            $introduction = $deleted->cache['introduction'] ?? null;
        }

        $tagMeta = UserTag::listWithMeta($userid, $user);

        $data = [
            'userid' => $userid,
            'birthday' => $birthday,
            'address' => $address,
            'introduction' => $introduction,
            'personal_tags' => $tagMeta['top'],
            'personal_tags_total' => $tagMeta['total'],
        ];

        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/users/lists 会员列表（限管理员）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName lists
     *
     * @apiParam {Object} [keys]        搜索条件
     * - keys.key               邮箱/电话/昵称/职位（赋值后keys.email、keys.tel、keys.nickname、keys.profession失效）
     * - keys.email             邮箱
     * - keys.tel               电话
     * - keys.nickname          昵称
     * - keys.profession        职位
     * - keys.identity          身份（如：admin、noadmin）
     * - keys.disable           是否离职
     *   - yes:     仅离职
     *   - all:     全部
     *   - 其他值:   仅在职（默认）
     * - keys.email_verity      邮箱是否认证
     *   - yes:     已认证
     *   - no:      未认证
     *   - 其他值:   全部（默认）
     * - keys.bot               是否包含机器人
     *   - yes:     仅机器人
     *   - all:     全部
     *   - 其他值:   非机器人（默认）
     * - keys.department        部门ID（0表示默认部门，不赋值获取所有部门）
     * - keys.checkin_face      人脸图片（get_checkin_data=1时有效）
     * - yes:     仅有人脸图片
     * - no:      无人脸图片
     * - all:     全部
     * - keys.checkin_mac       签到mac地址（get_checkin_data=1时有效）
     *
     * @apiParam {Number} [get_checkin_data]     获取签到mac地址
     * - 0: 不获取（默认）
     * - 1: 获取
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:50
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function lists()
    {
        User::auth('admin');
        //
        $builder = User::select(['*', 'nickname as nickname_original']);
        //
        $keys = Request::input('keys');
        $getCheckinData = intval(Request::input('get_checkin_data')) === 1;
        if (is_array($keys)) {
            if ($keys['key']) {
                if (str_contains($keys['key'], "@")) {
                    $builder->where("email", "like", "%{$keys['key']}%");
                } else {
                    $builder->where(function($query) use ($keys) {
                        $query->where("email", "like", "%{$keys['key']}%")
                            ->orWhere("tel", "like", "%{$keys['key']}%")
                            ->orWhere("nickname", "like", "%{$keys['key']}%")
                            ->orWhere("profession", "like", "%{$keys['key']}%");
                    });
                }
            } else {
                if ($keys['email']) {
                    $builder->where("email", "like", "%{$keys['email']}%");
                }
                if ($keys['tel']) {
                    $builder->where("tel", "like", "%{$keys['tel']}%");
                }
                if ($keys['nickname']) {
                    $builder->where("nickname", "like", "%{$keys['nickname']}%");
                }
                if ($keys['profession']) {
                    $builder->where("profession", "like", "%{$keys['profession']}%");
                }
            }
            if ($keys['identity']) {
                if (Base::leftExists($keys['identity'], "no")) {
                    $builder->where("identity", "not like", "%," . Base::leftDelete($keys['identity'], 'no') . ",%");
                } else {
                    $builder->where("identity", "like", "%,{$keys['identity']},%");
                }
            }
            if ($keys['disable'] === 'yes') {
                $builder->orderByDesc('disable_at');
                $builder->whereNotNull('disable_at');
            } elseif ($keys['disable'] !== 'all') {
                $builder->whereNull('disable_at');
            }
            if ($keys['email_verity'] === 'yes') {
                $builder->whereEmailVerity(1);
            } elseif ($keys['email_verity'] === 'no') {
                $builder->whereEmailVerity(0);
            }
            if ($keys['bot'] === 'yes') {
                $builder->where('bot', 1);
            } elseif ($keys['bot'] !== 'all') {
                $builder->where('bot', 0);
            }
            if (isset($keys['department'])) {
                if ($keys['department'] == '0') {
                    $builder->where(function($query) {
                        $query->where("department", "")->orWhere("department", ",,");
                    });
                } else {
                    // 关联user_departments表中owner_userid查询出负责人，重新排序，部门负责人始终在前面
                    $builder->where(function($query) use ($keys) {
                        $query->where("department", "like", "%,{$keys['department']},%");
                        $query->orWhereIn('userid', function ($query) use ($keys) {
                            $query->select('owner_userid')->from('user_departments')->where("id", "=", trim($keys['department'], ','));
                        });
                    });
                    $prefix = \DB::getTablePrefix();
                    $builder->selectRaw("if(EXISTS(select id from {$prefix}user_departments where owner_userid = userid and id={$keys['department']}),1,0) as is_principal");
                    $builder->orderBy("is_principal","desc");
                }
            }
            if ($getCheckinData) {
                if (isset($keys['checkin_face'])) {
                    $builder->whereIn('userid', function ($query) use ($keys) {
                        $query->select('userid')->from('user_checkin_faces')->whereNotNull("faceimg");
                    });
                }
                if (isset($keys['checkin_mac'])) {
                    $builder->whereIn('userid', function ($query) use ($keys) {
                        $query->select('userid')->from('user_checkin_macs')->where("mac", "like", "%{$keys['checkin_mac']}%");
                    });
                }
            }
        } else {
            $builder->whereNull('disable_at');
            $builder->where('bot', 0);
        }
        $list = $builder->orderByDesc('userid')->paginate(Base::getPaginate(50, 20));
        //
        if ($getCheckinData) {
            $list->transform(function (User $user) {
                $checkinFace = UserCheckinFace::select(['faceimg'])->whereUserid($user->userid)->first();
                $user->checkin_face = $checkinFace ? Base::fillUrl($checkinFace->faceimg) : '';
                $user->checkin_macs = UserCheckinMac::select(['id', 'mac', 'remark'])->whereUserid($user->userid)->orderBy('id')->get();
                return $user;
            });
        }
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/users/operation 操作会员（限管理员）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName operation
     *
     * @apiParam {Number} userid                会员ID
     * @apiParam {String} [type]                操作
     * - setadmin             设为管理员
     * - clearadmin           取消管理员
     * - settemp              设为临时帐号
     * - cleartemp            取消临时身份（取消临时帐号）
     * - checkin_macs         修改自动签到mac地址（需要参数 checkin_macs）
     * - checkin_face         修改签到人脸图片（需要参数 checkin_face）
     * - department           修改部门（需要参数 department）
     * - setdisable           设为离职（需要参数 disable_time、transfer_userid）
     * - cleardisable         取消离职
     * - delete               删除会员（需要参数 delete_reason）
     * @apiParam {String} [email]               邮箱地址
     * @apiParam {String} [tel]                 联系电话
     * @apiParam {String} [password]            新的密码
     * @apiParam {String} [nickname]            昵称
     * @apiParam {String} [profession]          职位
     * @apiParam {String} [checkin_macs]        自动签到mac地址
     * @apiParam {String} [checkin_face]        人脸图片地址
     * @apiParam {String} [department]          部门
     * @apiParam {String} [disable_time]        离职时间
     * @apiParam {String} [transfer_userid]     离职交接人
     * @apiParam {String} [delete_reason]       删除原因
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function operation()
    {
        $user = User::auth('admin');
        //
        $data = Request::all();
        $userid = intval($data['userid']);
        $type = $data['type'];
        //
        $userInfo = User::find($userid);
        if (empty($userInfo)) {
            return Base::retError('成员不存在或已被删除');
        }
        $userInfo->checkSystem(1);
        //
        $msg = '修改成功';
        $upArray = [];
        $upLdap = [];
        $transferUser = null;
        switch ($type) {
            case 'setadmin':
                $msg = '设置成功';
                $upArray['identity'] = array_diff($userInfo->identity, ['admin']);
                $upArray['identity'][] = 'admin';
                break;

            case 'clearadmin':
                $msg = '取消成功';
                $upArray['identity'] = array_diff($userInfo->identity, ['admin']);
                break;

            case 'settemp':
                $msg = '设置成功';
                $upArray['identity'] = array_diff($userInfo->identity, ['temp']);
                $upArray['identity'][] = 'temp';
                break;

            case 'cleartemp':
                $msg = '取消成功';
                $upArray['identity'] = array_diff($userInfo->identity, ['temp']);
                break;

            case 'checkin_macs':
                $list = is_array($data['checkin_macs']) ? $data['checkin_macs'] : [];
                $array = [];
                foreach ($list as $item) {
                    $item['mac'] = strtoupper($item['mac']);
                    if (Base::isMac($item['mac'])) {
                        $array[$item['mac']] = [
                            'mac' => $item['mac'],
                            'remark' => $item['remark'],
                        ];
                    }
                }
                return UserCheckinMac::saveMac($userInfo->userid, $array);

            case 'checkin_face':
                $faceimg = $data['checkin_face'] ?: '';
                return UserCheckinFace::saveFace($userInfo->userid, $userInfo->nickname, $faceimg, "管理员上传");

            case 'department':
                if (!is_array($data['department'])) {
                    $data['department'] = [];
                }
                if (count($data['department']) > 10) {
                    return Base::retError('最多只可加入10个部门');
                }
                foreach ($data['department'] as $id) {
                    if (!UserDepartment::whereId($id)->exists()) {
                        return Base::retError('修改部门不存在');
                    }
                }
                $upArray['department'] = $data['department'];
                break;

            case 'setdisable':
                $msg = '操作成功';
                if ($userInfo->userid === $user->userid) {
                    return Base::retError('不能操作自己离职');
                }
                $upArray['identity'] = array_diff($userInfo->identity, ['disable']);
                $upArray['identity'][] = 'disable';
                $upArray['disable_at'] = Carbon::parse($data['disable_time']);
                $transferUserid = Arr::get($data, 'transfer_userid');
                if (is_array($transferUserid)) {
                    $transferUserid = $transferUserid[0] ?? null;
                }
                $transferUserid = intval($transferUserid);
                if ($transferUserid > 0) {
                    $transferUser = User::find($transferUserid);
                    if (empty($transferUser)) {
                        return Base::retError('请选择正确的交接人');
                    }
                    if ($transferUser->userid === $userInfo->userid) {
                        return Base::retError('不能移交给自己');
                    }
                    if ($transferUser->isDisable()) {
                        return Base::retError('交接人已离职，请选择另一个交接人');
                    }
                }
                break;

            case 'cleardisable':
                $msg = '操作成功';
                $upArray['identity'] = array_diff($userInfo->identity, ['disable']);
                $upArray['disable_at'] = null;
                break;

            case 'delete':
                $msg = '删除成功';
                if ($userInfo->userid === $user->userid) {
                    return Base::retError('不能删除自己');
                }
                if (empty($data['delete_reason'])) {
                    return Base::retError('请填写删除原因');
                }
                $userInfo->deleteUser($data['delete_reason']);
                break;
        }
        if (isset($upArray['identity'])) {
            $upArray['identity'] = Base::arrayImplode($upArray['identity']);
        }
        if (isset($upArray['department'])) {
            $upArray['department'] = Base::arrayImplode($upArray['department']);
        }
        // 邮箱
        if (Arr::exists($data, 'email')) {
            $email = trim($data['email']);
            if (User::whereEmail($email)->where('userid', '!=', $userInfo->userid)->exists()) {
                return Base::retError('邮箱地址已存在');
            }
            if ($userInfo->isLdap()) {
                return Base::retError('LDAP 用户禁止修改邮箱');
            }
            $upArray['email'] = $email;
        }
        // 电话
        if (Arr::exists($data, 'tel')) {
            $tel = trim($data['tel']);
            if (User::whereTel($tel)->where('userid', '!=', $userInfo->userid)->exists()) {
                return Base::retError('联系电话已存在');
            }
            $upArray['tel'] = $tel;
            $upLdap['mobile'] = $tel;
        }
        // 密码
        if (Arr::exists($data, 'password')) {
            $password = trim($data['password']);
            User::passwordPolicy($password);
            $upArray['encrypt'] = Base::generatePassword(6);
            $upArray['password'] = Doo::md5s($password, $upArray['encrypt']);
            $upArray['changepass'] = 1;
            $upLdap['userPassword'] = $password;
        }
        // 昵称
        if (Arr::exists($data, 'nickname')) {
            $nickname = trim($data['nickname']);
            if ($nickname && mb_strlen($nickname) < 2) {
                return Base::retError('昵称不可以少于2个字');
            } elseif (mb_strlen($nickname) > 20) {
                return Base::retError('昵称最多只能设置20个字');
            } else {
                $upArray['nickname'] = $nickname;
                $upArray['az'] = Base::getFirstCharter($nickname);
                $upArray['pinyin'] = Base::cn2pinyin($nickname);
                $upLdap['displayName'] = $nickname;
            }
        }
        // 职位/职称
        if (Arr::exists($data, 'profession')) {
            $profession = trim($data['profession']);
            if ($profession && mb_strlen($profession) < 2) {
                return Base::retError('职位/职称不可以少于2个字');
            } elseif (mb_strlen($profession) > 20) {
                return Base::retError('职位/职称最多只能设置20个字');
            } else {
                $upArray['profession'] = $profession;
                $upLdap['employeeType'] = $profession;
            }
        }
        if ($upArray) {
            AbstractModel::transaction(function() use ($upLdap, $user, $type, $upArray, $userInfo, $transferUser) {
                $exitIds = array_diff($userInfo->department, Base::explodeInt($upArray['department']));
                $joinIds = array_diff(Base::explodeInt($upArray['department']), $userInfo->department);
                $userInfo->updateInstance($upArray);
                $userInfo->save();
                LdapUser::userUpdate($userInfo->email, $upLdap);
                if ($type === 'department') {
                    $userids = [$userInfo->userid];
                    // 退出群组
                    $exitDepartments = UserDepartment::whereIn('id', $exitIds)->get();
                    foreach ($exitDepartments as $exitDepartment) {
                        if ($exitDepartment->dialog_id > 0 && $exitDialog = WebSocketDialog::find($exitDepartment->dialog_id)) {
                            $exitDialog->exitGroup($userids, 'remove', false);
                            $exitDialog->pushMsg("groupExit", null, $userids);
                        }
                    }
                    // 加入群组
                    $joinDepartments = UserDepartment::whereIn('id', $joinIds)->get();
                    foreach ($joinDepartments as $joinDepartment) {
                        if ($joinDepartment->dialog_id > 0 && $joinDialog = WebSocketDialog::find($joinDepartment->dialog_id)) {
                            $joinDialog->joinGroup($userids, 0, true);
                            $joinDialog->pushMsg("groupJoin", null, $userids);
                        }
                    }
                } elseif ($type === 'setdisable' && $transferUser) {
                    $userTransfer = UserTransfer::createInstance([
                        'original_userid' => $userInfo->userid,
                        'new_userid' => $transferUser->userid,
                    ]);
                    $userTransfer->save();
                    go(function () use ($userTransfer) {
                        Coroutine::sleep(0.1);
                        $userTransfer->start();
                    });
                } elseif ($type === 'cleardisable') {
                    // 取消离职重新加入全员群组
                    if (Base::settingFind('system', 'all_group_autoin', 'yes') === 'yes') {
                        $dialog = WebSocketDialog::whereGroupType('all')->orderByDesc('id')->first();
                        $dialog?->joinGroup($userInfo->userid, $user->userid);
                    }
                }
            });
        }
        //
        return Base::retSuccess($msg, $userInfo);
    }

    /**
     * @api {get} api/users/email/verification 邮箱验证
     *
     * @apiDescription 不需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName email__verification
     *
     * @apiParam {String} code           验证参数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function email__verification()
    {
        $data = Request::input();
        // 表单验证
        Base::validator($data, [
            'code.required' => '验证码不能为空',
        ]);
        //
        $res = UserEmailVerification::whereCode($data['code'])->first();
        if (empty($res)) {
            return Base::retError('无效连接,请重新注册');
        }

        // 如果已经校验过
        if (intval($res->status) === 1)
            return Base::retError('链接已经使用过', ['code' => 2]);

        $oldTime = Carbon::parse($res->created_at)->timestamp;
        $time = Timer::Time();

        // 30分钟失效
        if (abs($time - $oldTime) > 1800) {
            return Base::retError("链接已失效，请重新登录/注册");
        }
        UserEmailVerification::whereCode($data['code'])->update([
            'status' => 1
        ]);
        User::whereUserid($res->userid)->update([
            'email_verity' => 1
        ]);

        return Base::retSuccess('绑定邮箱成功');
    }

    /**
     * @api {get} api/users/umeng/alias 设置友盟别名
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName umeng__alias
     *
     * @apiParam {String} action
     * - update: 更新（默认）
     * - remove: 删除
     * @apiParam {String} alias           别名
     * @apiParam {String} [userAgent]     浏览器信息
     * @apiParam {String} [deviceModel]   设备型号
     * @apiParam {String} [isNotified]    是否有通知权限（0不通知、1通知）
     * @apiParam {Number} [isDebug]       是否调试（0不调试、1调试）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function umeng__alias()
    {
        $data = Request::input();
        // 判断是否调试
        if (intval($data['isDebug'])) {
            return Base::retError('调试模式下不允许使用');
        }
        // 表单验证
        Base::validator($data, [
            'alias.required' => '别名不能为空',
            'alias.between:2,20' => '别名的长度在2-20个字符',
        ]);
        //
        if ($data['action'] === 'remove') {
            if ($data['alias']) {
                UmengAlias::whereAlias($data['alias'])->delete();
            }
            return Base::retSuccess('删除成功');
        }
        //
        if (!in_array(Base::platform(), ['ios', 'android'])) {
            return Base::retError('设备类型错误');
        }
        //
        $user = User::auth();
        $inArray = [
            'userid' => $user->userid,
            'alias' => $data['alias'],
            'platform' => Base::platform(),
        ];
        $version = $data['appVersion'] ? ($data['appVersionName'] . " ({$data['appVersion']})") : '';
        $isNotified = trim($data['isNotified']) === 'true' || $data['isNotified'] === true ? 1 : intval($data['isNotified']);
        $row = UmengAlias::where($inArray);
        if ($row->exists()) {
            $row->update([
                'ua' => $data['userAgent'],
                'device' => $data['deviceModel'],
                'device_hash' => UserDevice::check(),
                'version' => $version,
                'is_notified' => $isNotified,
                'updated_at' => Carbon::now()
            ]);
            return Base::retSuccess('别名已存在');
        }
        $row = UmengAlias::createInstance(array_merge($inArray, [
            'ua' => $data['userAgent'],
            'device' => $data['deviceModel'],
            'device_hash' => UserDevice::check(),
            'version' => $version,
            'is_notified' => $isNotified,
        ]));
        if ($row->save()) {
            return Base::retSuccess('添加成功');
        } else {
            return Base::retError('添加错误');
        }
    }

    /**
     * @api {get} api/users/meeting/open 【会议】创建会议、加入会议
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName meeting__open
     *
     * @apiParam {String} type                      类型
     * - create: 创建会议，有效参数：name、userids
     * - join: 加入会议，有效参数：meetingid (必填)
     * @apiParam {String} [meetingid]               频道ID（不是数字）
     * @apiParam {String} [name]                    会话ID
     * @apiParam {String} [sharekey]                分享的key
     * @apiParam {String} [username]                用户名称
     * @apiParam {String} [userimg]                 用户头像
     * @apiParam {Array} [userids]                  邀请成员
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function meeting__open()
    {
        $type = trim(Request::input('type'));
        $meetingid = str_replace(' ', '', trim(Request::input('meetingid')));
        $name = trim(Request::input('name'));
        $userids = Request::input('userids');
        $sharekey = trim(Request::input('sharekey'));
        $username = trim(Request::input('username'));
        $userimg = trim(Request::input('userimg')) ?: Base::fillUrl('avatar/' . $username . '.png');
        $user = null;
        if (!empty($sharekey) && $type === 'join') {
            if (!Meeting::getShareInfo($sharekey)) {
                return Base::retError('分享链接已过期');
            }
        } else {
            $user = User::auth();
        }
        $isCreate = false;
        // 创建、加入
        if ($type === 'join') {
            $meeting = Meeting::whereMeetingid($meetingid)->first();
            if (empty($meeting)) {
                return Base::retError('频道ID不存在');
            }
            if ($meeting->end_at) {
                return Base::retError('会议已结束');
            }
            $meeting->updated_at = Carbon::now();
            $meeting->save();
        } elseif ($type === 'create') {
            $meetingid = strtoupper(Base::generatePassword(11, 1));
            $name = $name ?: Doo::translate("{$user?->nickname} 发起的会议");
            $channel = "DooTask:" . substr(md5($meetingid . env("APP_KEY")), 16);
            $meeting = Meeting::createInstance([
                'meetingid' => $meetingid,
                'name' => $name,
                'channel' => $channel,
                'userid' => $user?->userid
            ]);
            $meeting->save();
            $isCreate = true;
        } else {
            return Base::retError('参数错误');
        }
        $data = $meeting->toArray();
        // 创建令牌
        $meetingSetting = Base::setting('meetingSetting');
        if ($meetingSetting['open'] !== 'open') {
            return Base::retError('会议功能未开启，请联系管理员开启');
        }
        if (empty($meetingSetting['appid']) || empty($meetingSetting['app_certificate'])) {
            return Base::retError('会议功能配置错误，请联系管理员');
        }
        $uid = intval(str_pad(Base::generatePassword(4, 1), 9, 8, STR_PAD_LEFT));
        if ($user) {
            $uid = intval(str_pad(Base::generatePassword(5, 1), 6, 9, STR_PAD_LEFT) . $user->userid);
        }
        try {
            $service = new AgoraTokenGenerator($meetingSetting['appid'], $meetingSetting['app_certificate'], $meeting->channel, $uid);
        } catch (\Exception $e) {
            return Base::retError($e->getMessage());
        }
        $token = $service->buildToken();
        if (empty($token)) {
            return Base::retError('会议令牌创建失败');
        }
        // 发送给邀请人
        $msgs = [];
        if ($isCreate) {
            foreach ($userids as $userid) {
                if (!User::whereUserid($userid)->exists()) {
                    continue;
                }
                $botUser = User::botGetOrCreate('meeting-alert');
                $dialog = WebSocketDialog::checkUserDialog($botUser, $userid);
                if ($dialog) {
                    $res = WebSocketDialogMsg::sendMsg(null, $dialog->id, 'meeting', $data, $user->userid);
                    if (Base::isSuccess($res)) {
                        $msgs[] = $res['data'];
                    }
                }
            }
        }
        //
        $data['appid'] = $meetingSetting['appid'];
        $data['uid'] = $uid;
        $data['userimg'] = $sharekey ? $userimg : $user?->userimg;
        $data['nickname'] = $sharekey ? $username : $user?->nickname;
        $data['token'] = $token;
        $data['msgs'] = $msgs;
        //
        Meeting::setTouristInfo($data);
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/users/tags/lists 获取个性标签列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName tags__lists
     *
     * @apiParam {Number} [userid]          会员ID（不传默认为当前用户）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} data:
    {
        "list": [
            {
                "id": 1,
                "name": "认真负责",
                "recognition_total": 3,
                "recognized": true,
                "can_edit": true,
                "can_delete": true
            }
        ],
        "top": [ ],
        "total": 1
    }
     */
    public function tags__lists()
    {
        $viewer = User::auth();
        $userid = intval(Request::input('userid')) ?: $viewer->userid;
        $target = User::whereUserid($userid)->first();
        if (empty($target)) {
            return Base::retError('会员不存在');
        }
        return Base::retSuccess('success', UserTag::listWithMeta($target->userid, $viewer));
    }

    /**
     * @api {post} api/users/tags/add 新增个性标签
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName tags__add
     *
     * @apiParam {Number} [userid]          会员ID（不传默认为当前用户）
     * @apiParam {String} name              标签名称（1-20个字符）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据，同“获取个性标签列表”
     */
    public function tags__add()
    {
        $viewer = User::auth();
        $userid = intval(Request::input('userid')) ?: $viewer->userid;
        $target = User::whereUserid($userid)->first();
        if (empty($target)) {
            return Base::retError('会员不存在');
        }

        $name = trim((string) Request::input('name'));
        if ($name === '') {
            return Base::retError('请输入个性标签');
        }
        if (mb_strlen($name) > 20) {
            return Base::retError('标签名称最多只能设置20个字');
        }
        if (UserTag::where('user_id', $userid)->where('name', $name)->exists()) {
            return Base::retError('标签已存在');
        }
        if (UserTag::where('user_id', $userid)->count() >= 100) {
            return Base::retError('每位会员最多添加100个标签');
        }

        $tag = UserTag::create([
            'user_id' => $userid,
            'name' => $name,
            'created_by' => $viewer->userid,
            'updated_by' => $viewer->userid,
        ]);
        $tag->save();

        return Base::retSuccess('添加成功', UserTag::listWithMeta($userid, $viewer));
    }

    /**
     * @api {post} api/users/tags/update 修改个性标签
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName tags__update
     *
     * @apiParam {Number} tag_id           标签ID
     * @apiParam {String} name             标签名称（1-20个字符）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据，同“获取个性标签列表”
     */
    public function tags__update()
    {
        $viewer = User::auth();
        $tagId = intval(Request::input('tag_id'));
        $name = trim((string) Request::input('name'));
        if ($tagId <= 0) {
            return Base::retError('参数错误');
        }
        if ($name === '') {
            return Base::retError('请输入个性标签');
        }
        if (mb_strlen($name) > 20) {
            return Base::retError('标签名称最多只能设置20个字');
        }
        $tag = UserTag::find($tagId);
        if (empty($tag)) {
            return Base::retError('标签不存在');
        }
        if (!$tag->canManage($viewer)) {
            return Base::retError('无权操作该标签');
        }
        if ($name !== $tag->name && UserTag::where('user_id', $tag->user_id)->where('name', $name)->where('id', '!=', $tag->id)->exists()) {
            return Base::retError('标签已存在');
        }

        if ($name !== $tag->name) {
            $tag->updateInstance([
                'name' => $name,
                'updated_by' => $viewer->userid,
            ]);
        } else {
            $tag->updateInstance([
                'updated_by' => $viewer->userid,
            ]);
        }
        $tag->save();

        return Base::retSuccess('保存成功', UserTag::listWithMeta($tag->user_id, $viewer));
    }

    /**
     * @api {post} api/users/tags/delete 删除个性标签
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName tags__delete
     *
     * @apiParam {Number} tag_id           标签ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据，同“获取个性标签列表”
     */
    public function tags__delete()
    {
        $viewer = User::auth();
        $tagId = intval(Request::input('tag_id'));
        if ($tagId <= 0) {
            return Base::retError('参数错误');
        }
        $tag = UserTag::find($tagId);
        if (empty($tag)) {
            return Base::retError('标签不存在');
        }
        if (!$tag->canManage($viewer)) {
            return Base::retError('无权操作该标签');
        }

        $userId = $tag->user_id;
        $tag->delete();

        return Base::retSuccess('删除成功', UserTag::listWithMeta($userId, $viewer));
    }

    /**
     * @api {post} api/users/tags/recognize 认可个性标签
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName tags__recognize
     *
     * @apiParam {Number} tag_id           标签ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据，同“获取个性标签列表”
     */
    public function tags__recognize()
    {
        $viewer = User::auth();
        $tagId = intval(Request::input('tag_id'));
        if ($tagId <= 0) {
            return Base::retError('参数错误');
        }
        $tag = UserTag::find($tagId);
        if (empty($tag)) {
            return Base::retError('标签不存在');
        }

        $recognition = UserTagRecognition::where('tag_id', $tagId)
            ->where('user_id', $viewer->userid)
            ->first();
        if ($recognition) {
            $recognition->delete();
            $message = '已取消认可';
        } else {
            UserTagRecognition::create([
                'tag_id' => $tagId,
                'user_id' => $viewer->userid,
            ]);
            $message = '认可成功';
        }

        return Base::retSuccess($message, UserTag::listWithMeta($tag->user_id, $viewer));
    }

    /**
     * @api {get} api/users/meeting/link 【会议】获取分享链接
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName meeting__link
     *
     * @apiParam {String} meetingid               频道ID（不是数字）
     * @apiParam {String} [sharekey]              分享的key
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function meeting__link()
    {
        $meetingid = trim(Request::input('meetingid'));
        $sharekey = trim(Request::input('sharekey'));
        if (empty($sharekey) || !Meeting::getShareInfo($sharekey)) {
            User::auth();
        }
        $meeting = Meeting::whereMeetingid($meetingid)->first();
        if (empty($meeting)) {
            return Base::retError('频道ID不存在');
        }
        return Base::retSuccess('success', $meeting->getShareLink());
    }

    /**
     * @api {get} api/users/meeting/tourist 【会议】游客信息
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName meeting__tourist
     *
     * @apiParam {String} tourist_id     游客ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function meeting__tourist()
    {
        $touristId = trim(Request::input('tourist_id'));
        if ($touristInfo = Meeting::getTouristInfo($touristId)) {
            return Base::retSuccess('success', $touristInfo);
        }
        return Base::retError('Id不存在');
    }

    /**
     * @api {get} api/users/meeting/invitation 【会议】发送邀请
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName meeting__invitation
     *
     * @apiParam {String} meetingid               频道ID（不是数字）
     * @apiParam {Array} userids                  邀请成员
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function meeting__invitation()
    {
        $user = User::auth();
        //
        $meetingid = trim(Request::input('meetingid'));
        $userids = Request::input('userids');
        //
        $meeting = Meeting::whereMeetingid($meetingid)->first();
        if (empty($meeting)) {
            return Base::retError('频道ID不存在');
        }
        $data = $meeting->toArray();
        // 发送给邀请人
        $msgs = [];
        foreach ($userids as $userid) {
            if (!User::whereUserid($userid)->exists()) {
                continue;
            }
            $botUser = User::botGetOrCreate('meeting-alert');
            $dialog = WebSocketDialog::checkUserDialog($botUser, $userid);
            if ($dialog) {
                $res = WebSocketDialogMsg::sendMsg(null, $dialog->id, 'meeting', $data, $user->userid);
                if (Base::isSuccess($res)) {
                    $msgs[] = $res['data'];
                }
            }
        }
        //
        $data['msgs'] = $msgs;
        return Base::retSuccess('发送邀请成功', $data);
    }

    /**
     * @api {get} api/users/email/send 发送邮箱验证码
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName email__send
     *
     * @apiParam {Number} type               邮件类型
     * @apiParam {String} email              邮箱地址
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function email__send()
    {
        $user = User::auth();
        //
        $type = Request::input('type', 2);
        $email = Request::input('email');
        if (!$email) {
            return Base::retError('请输入新邮箱地址');
        }
        if (!Base::isEmail($email)) {
            return Base::retError('邮箱地址错误');
        }
        if ($user->email == $email && $type == 2) {
            return Base::retError('不能与旧邮箱一致');
        }
        if ($user->email != $email && $type == 3) {
            return Base::retError('与当前登录邮箱不一致');
        }
        if (User::where('userid', '<>', $user->userid)->whereEmail($email)->exists()) {
            return Base::retError('邮箱地址已存在');
        }
        UserEmailVerification::userEmailSend($user, $type, $email);
        return Base::retSuccess('发送成功');
    }

    /**
     * @api {get} api/users/email/edit 修改邮箱
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName edit__email
     *
     * @apiParam {String} newEmail          新邮箱地址
     * @apiParam {String} code              邮箱验证码
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function email__edit()
    {
        $user = User::auth();
        //
        $user->checkSystem();
        //
        if ($user->isLdap()) {
            return Base::retError('LDAP 用户禁止修改邮箱');
        }
        //
        $newEmail = trim(Request::input('newEmail'));
        $code = trim(Request::input('code'));
        if (!$newEmail) {
            return Base::retError('请输入新邮箱地址');
        }
        if (!Base::isEmail($newEmail)) {
            return Base::retError('邮箱地址错误');
        }

        $isRegVerify = Base::settingFind('emailSetting', 'reg_verify') === 'open';
        if ($isRegVerify) {
            UserEmailVerification::verify($newEmail, $code, 2);
        }

        $user->email = $newEmail;
        $user->save();
        User::generateToken($user);
        return Base::retSuccess('修改成功', $user);
    }

    /**
     * @api {get} api/users/delete/account 删除帐号
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName delete__account
     *
     * @apiParam {String} email          帐号邮箱
     * @apiParam {String} code           邮箱验证码
     * @apiParam {String} reason         注销理由
     * @apiParam {String} password       登录密码
     * @apiParam {Number} type           类型
     * - warning: 提交校验
     * - confirm: 确认删除
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function delete__account()
    {
        $user = User::auth();
        //
        $user->checkSystem(1);
        //
        $email = Request::input('email');
        $code = Request::input('code');
        $reason = Request::input('reason');
        $password = Request::input('password');
        $type = Request::input('type');
        if (!$email) {
            return Base::retError('请输入新邮箱地址');
        }
        if (!Base::isEmail($email)) {
            return Base::retError('邮箱地址错误');
        }
        if ($user->email != $email) {
            return Base::retError('与当前登录邮箱不一致');
        }

        $isRegVerify = Base::settingFind('emailSetting', 'reg_verify') === 'open';
        if ($isRegVerify) {
            UserEmailVerification::verify($email, $code, 3);
        } else {
            if (!$password) {
                return Base::retError('请输入登录密码');
            }
            if ($user->password != Doo::md5s($password, $user->encrypt)) {
                return Base::retError('密码错误');
            }
        }
        if ($type == 'confirm') {
            if ($user->deleteUser($reason)) {
                return Base::retSuccess('删除成功', $user);
            } else {
                return Base::retError('删除失败');
            }
        }
        return Base::retSuccess('success', $user);
    }

    /**
     * @api {get} api/users/department/list 部门列表（限管理员）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName department__list
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function department__list()
    {
        User::auth('admin');
        //
        return Base::retSuccess('success', UserDepartment::orderBy('id')->get());
    }

    /**
     * @api {get} api/users/department/add 新建、修改部门（限管理员）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName department__add
     *
     * @apiParam {Number} [id]             部门id，留空为创建部门
     * @apiParam {String} name             部门名称
     * @apiParam {Number} [parent_id]      上级部门ID
     * @apiParam {Number} owner_userid     部门负责人ID
     * @apiParam {String} [dialog_group]   部门群（仅创建部门时有效）
     * - new: 创建（默认）
     * - use: 使用现有群
     * @apiParam {Number} [dialog_useid]   使用现有群ID（dialog_group=use时有效）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function department__add()
    {
        User::auth('admin');
        //
        $id = intval(Request::input('id'));
        $name = trim(Request::input('name'));
        $parent_id = intval(Request::input('parent_id'));
        $owner_userid = intval(Request::input('owner_userid'));
        $dialog_group = trim(Request::input('dialog_group'));
        $dialog_useid = $dialog_group === 'use' ? intval(Request::input('dialog_useid')) : 0;
        //
        if (mb_strlen($name) < 2 || mb_strlen($name) > 20) {
            return Base::retError('部门名称长度限制2-20个字');
        }
        if (preg_match('/[\Q~!@#$%^&*()+-_=.:?<>,\E]/', $name)) {
            return Base::retError('部门名称不能包含特殊符号');
        }
        if (str_contains($name, '(M)')) {
            return Base::retError('部门名称不能包含：(M)');
        }
        //
        if ($id > 0) {
            $userDepartment = UserDepartment::find($id);
            if (empty($userDepartment)) {
                return Base::retError('部门不存在或已被删除');
            }
        } else {
            if (UserDepartment::count() > 200) {
                return Base::retError('最多只能创建200个部门');
            }
            $userDepartment = UserDepartment::createInstance();
        }
        //
        if ($parent_id > 0) {
            $parentDepartment = UserDepartment::find($parent_id);
            if (empty($parentDepartment)) {
                return Base::retError('上级部门不存在或已被删除');
            }
            if (count($parentDepartment->parents()) > 2) {
                return Base::retError('部门层级最多只能创建3级');
            }
            if ($id > 0 && UserDepartment::whereParentId($id)->whereId($parent_id)->exists()) {
                return Base::retError('不能选择自己的子部门作为上级部门');
            }
            if (UserDepartment::whereParentId($parent_id)->count() >= 20) {
                return Base::retError('每个部门最多只能创建20个子部门');
            }
        }
        if (empty($owner_userid) || !User::whereUserid($owner_userid)->exists()) {
            return Base::retError('请选择正确的部门负责人');
        }
        if (UserDepartment::whereOwnerUserid($owner_userid)->count() >= 10) {
            return Base::retError('每个用户最多只能负责10个部门');
        }
        //
        $userDepartment->saveDepartment([
            'name' => $name,
            'parent_id' => $parent_id,
            'owner_userid' => $owner_userid,
        ], $dialog_useid);
        Cache::forever("UserDepartment::rand", Base::generatePassword());
        //
        return Base::retSuccess($id > 0 ? '保存成功' : '新建成功');
    }

    /**
     * @api {get} api/users/department/del 删除部门（限管理员）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName department__del
     *
     * @apiParam {Number} id             部门id
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function department__del()
    {
        User::auth('admin');
        //
        $id = intval(Request::input('id'));
        //
        $userDepartment = UserDepartment::find($id);
        if (empty($userDepartment)) {
            return Base::retError('部门不存在或已被删除');
        }
        if (UserDepartment::whereParentId($id)->exists()) {
            return Base::retError('含有子部门无法删除');
        }
        $userDepartment->deleteDepartment();
        Cache::forever("UserDepartment::rand", Base::generatePassword());
        //
        return Base::retSuccess('删除成功');
    }

    /**
     * @api {get} api/users/department/sync 同步部门成员（限管理员）
     *
     * @apiDescription 需要token身份，将子部门成员同步到当前部门
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName department__sync
     *
     * @apiParam {Number} id             部门id
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function department__sync()
    {
        User::auth('admin');
        //
        $id = intval(Request::input('id'));
        //
        $userDepartment = UserDepartment::find($id);
        if (empty($userDepartment)) {
            return Base::retError('部门不存在或已被删除');
        }

        // 获取所有子部门（递归）
        $subDepartmentIds = UserDepartment::getAllSubDepartmentIds($id);
        if (empty($subDepartmentIds)) {
            return Base::retSuccess('同步完成，子部门中没有成员需要同步', [
                'synced_count' => 0,
                'already_in_dept_count' => 0,
                'sub_department_ids' => $subDepartmentIds
            ]);
        }

        // 获取子部门中的所有成员
        $subDepartmentMembers = [];
        foreach ($subDepartmentIds as $subId) {
            $users = User::where("department", "like", "%,{$subId},%")
                ->whereNull('disable_at')
                ->get();
            foreach ($users as $user) {
                if (!isset($subDepartmentMembers[$user->userid])) {
                    $subDepartmentMembers[$user->userid] = $user->userid;
                }
            }
        }

        if (empty($subDepartmentMembers)) {
            return Base::retSuccess('同步完成，子部门中没有成员需要同步', [
                'synced_count' => 0,
                'already_in_dept_count' => 0,
                'sub_department_ids' => $subDepartmentIds
            ]);
        }

        // 将子部门成员添加到当前部门
        $syncedCount = 0;
        $alreadyInDeptCount = 0;

        AbstractModel::transaction(function () use ($id, $subDepartmentMembers, &$syncedCount, &$alreadyInDeptCount) {
            foreach ($subDepartmentMembers as $userid) {
                $user = User::find($userid);
                if ($user && $user->disable_at === null) {
                    $userDepartments = $user->department;
                    if (!in_array($id, $userDepartments)) {
                        $userDepartments[] = $id;
                        $user->department = Base::arrayImplode($userDepartments);
                        $user->save();
                        $syncedCount++;
                    } else {
                        $alreadyInDeptCount++;
                    }
                }
            }
        });

        if ($userDepartment->dialog_id > 0) {
            $departmentMemberIds = User::where("department", "like", "%,{$id},%")
                ->whereNull('disable_at')
                ->pluck('userid')
                ->toArray();
            $departmentMemberIds = array_map('intval', $departmentMemberIds);

            $existingGroupUserIds = WebSocketDialogUser::whereDialogId($userDepartment->dialog_id)
                ->pluck('userid')
                ->toArray();
            $existingGroupUserIds = array_map('intval', $existingGroupUserIds);
            $missingInGroup = array_values(array_diff($departmentMemberIds, $existingGroupUserIds));

            if (!empty($missingInGroup) && $dialog = WebSocketDialog::find($userDepartment->dialog_id)) {
                $dialog->joinGroup($missingInGroup, 0, true);
                $dialog->pushMsg("groupJoin", null, $missingInGroup);
            }
        }

        $message = "同步完成，共同步 {$syncedCount} 个成员";
        if ($alreadyInDeptCount > 0) {
            $message .= "，其中 {$alreadyInDeptCount} 个成员已在当前部门";
        }

        return Base::retSuccess($message, [
            'synced_count' => $syncedCount,
            'already_in_dept_count' => $alreadyInDeptCount,
            'sub_department_ids' => $subDepartmentIds
        ]);
    }

    /**
     * @api {get} api/users/checkin/get 获取签到设置
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName checkin__get
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function checkin__get()
    {
        $user = User::auth();
        //
        $list = UserCheckinMac::whereUserid($user->userid)->orderBy('id')->get();
        $userface = UserCheckinFace::whereUserid($user->userid)->first();

        $data = [
            'list' => $list,
            'faceimg' => $userface ? Base::fillUrl($userface->faceimg) : ''
        ];
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {post} api/users/checkin/save 保存签到设置
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName checkin__save
     *
     * @apiParam {String} type      类型
     * - face: 人脸识别设置
     * - mac: MAC设置
     * @apiParam {String} faceimg   人脸图片地址
     * @apiParam {Array} list       优先级数据，格式：[{mac,remark}]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function checkin__save()
    {
        $user = User::auth();
        //
        $setting = Base::setting('checkinSetting');
        if ($setting['open'] !== 'open') {
            return Base::retError('此功能未开启，请联系管理员开启');
        }
        //
        $type = Request::input('type');
        $list = Request::input('list');
        $faceimg = Request::input('faceimg');
        //
        $data = [
            'list' => $list,
            'faceimg' =>  $faceimg
        ];
        switch ($type) {
            case 'face':
                if ($setting['face_upload'] !== 'open') {
                    return Base::retError('未开放修改权限，请联系管理员');
                }
                UserCheckinFace::saveFace($user->userid, $user->nickname(), $faceimg, "用户上传");
                break;

            case 'mac':
                if ($setting['edit'] !== 'open') {
                    return Base::retError('未开放修改权限，请联系管理员');
                }
                $array = [];
                if (empty($list) || !is_array($list)) {
                    return Base::retError('参数错误');
                }
                foreach ($list as $item) {
                    $item = Base::newTrim($item);
                    if (Base::isMac($item['mac'])) {
                        $mac = strtoupper($item['mac']);
                        $array[$mac] = [
                            'mac' => $mac,
                            'remark' => substr($item['remark'], 0, 50),
                        ];
                    }
                }
                if (count($array) > 3) {
                    return Base::retError('最多只能添加3个MAC地址');
                }
                $saveMacRes = UserCheckinMac::saveMac($user->userid, $array);
                $data['list'] = $saveMacRes['data'];
                break;

            default:
                return Base::retError('参数错误');
        }
        //
        return Base::retSuccess('修改成功', $data);
    }

    /**
     * @api {get} api/users/checkin/list 获取签到数据
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName checkin__list
     *
     * @apiParam {String} ym            年-月（如：2020-01）
     * @apiParam {Number} [before]      取月份之前的数据（单位：月数，最大3）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function checkin__list()
    {
        $user = User::auth();
        //
        $ym = trim(Request::input('ym'));
        $start = Carbon::parse(date("Y-m-01 00:00:00", strtotime($ym)));
        $end = (clone $start)->addMonth()->subSecond();
        //
        $before = min(3, intval(Request::input('before')));
        if ($before > 0) {
            $start = $start->subMonths($before);
        }
        //
        $recordTimes = UserCheckinRecord::getTimes($user->userid, [$start, $end]);
        $array = [];
        $startT = $start->timestamp;
        $endT = $end->timestamp;
        while ($startT < $endT) {
            $sameDate = date("Y-m-d", $startT);
            $sameTimes = $recordTimes[$sameDate] ?? [];
            if ($sameTimes) {
                $array[] = [
                    'date' => $sameDate,
                    'section' => UserCheckinRecord::atSection($sameTimes),
                ];
            }
            $startT += 86400;
        }
        //
        return Base::retSuccess('success', $array);
    }

    /**
     * @api {get} api/users/socket/status 获取socket状态
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName socket__status
     *
     * @apiParam {String} [fd]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function socket__status()
    {
        $row = WebSocket::select(['id', 'fd', 'userid', 'updated_at'])->whereFd(Base::headerOrInput('fd'))->first();
        if (empty($row)) {
            return Base::retError('error');
        }
        return Base::retSuccess('success', $row);
    }

    /**
     * @api {get} api/users/key/client 客户端KEY
     *
     * @apiDescription 获取客户端KEY，用于加密数据发送给服务端
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName key__client
     *
     * @apiParam {String} [client_id]        客户端ID（希望不变的，除非清除浏览器缓存或者卸载应用）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function key__client()
    {
        $clientId = (trim(Request::input('client_id')) ?: Base::generatePassword(6)) . Doo::userId();
        //
        $cacheKey = "KeyPair::" . $clientId;
        if (Cache::has($cacheKey)) {
            $cacheData = Base::json2array(Cache::get($cacheKey));
            if ($cacheData['private_key']) {
                return Base::retSuccess('success', [
                    'type' => 'pgp',
                    'id' => $clientId,
                    'key' => $cacheData['public_key'],
                ]);
            }
        }
        //
        $name = Doo::userEmail() ?: Base::generatePassword(6);
        $email = Doo::userEmail() ?: 'aa@bb.cc';
        $data = Doo::pgpGenerateKeyPair($name, $email, Base::generatePassword());
        Cache::put("KeyPair::" . $clientId, Base::array2json($data), Carbon::now()->addQuarter());
        //
        return Base::retSuccess('success', [
            'type' => 'pgp',
            'id' => $clientId,
            'key' => $data['public_key'],
        ]);
    }

    /**
     * @api {get} api/users/bot/list 机器人列表
     *
     * @apiDescription 需要token身份，获取我的机器人列表
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName bot__list
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function bot__list()
    {
        // 获取当前认证用户
        $user = User::auth();

        // 使用连表查询一次性获取所有机器人数据
        $bots = User::join('user_bots', 'user_bots.bot_id', '=', 'users.userid')
            ->where('user_bots.userid', $user->userid)
            ->select([
                'users.userid',
                'users.nickname',
                'users.userimg',
                'user_bots.clear_day',
                'user_bots.webhook_url',
                'user_bots.webhook_events'
            ])
            ->orderByDesc('id')
            ->get()
            ->toArray();
        foreach ($bots as &$bot) {
            $bot['id'] = $bot['userid'];
            $bot['name'] = $bot['nickname'];
            $bot['avatar'] = $bot['userimg'];
            $bot['system_name'] = UserBot::systemBotName($bot['name']);
            $bot['webhook_events'] = UserBot::normalizeWebhookEvents($bot['webhook_events'] ?? null, empty($bot['webhook_events']));
            unset($bot['userid'], $bot['nickname'], $bot['userimg']);
        }

        // 返回成功响应，将机器人列表包装在list字段中
        return Base::retSuccess('success', [
            'list' => $bots
        ]);
    }

    /**
     * @api {get} api/users/bot/info 机器人信息
     *
     * @apiDescription 需要token身份，获取我的机器人信息
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName bot__info
     *
     * @apiParam {Number} id        机器人ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function bot__info()
    {
        $user = User::auth();
        //
        $botId = intval(Request::input('id'));
        $botUser = User::whereUserid($botId)->whereBot(1)->first();
        if (empty($botUser)) {
            return Base::retError('机器人不存在');
        }
        $userBot = UserBot::whereBotId($botUser->userid)->whereUserid($user->userid)->first();
        if (empty($userBot)) {
            if (UserBot::systemBotName($botUser->email)) {
                // 系统机器人（仅限管理员）
                if (!$user->isAdmin()) {
                    return Base::retError('权限不足');
                }
            } else {
                // 其他用户的机器人（仅限主人）
                return Base::retError('不是你的机器人');
            }
        }
        //
        $data = [
            'id' => $botUser->userid,
            'name' => $botUser->nickname,
            'avatar' => $botUser->userimg,
            'clear_day' => 0,
            'webhook_url' => '',
            'webhook_events' => [UserBot::WEBHOOK_EVENT_MESSAGE],
            'system_name' => UserBot::systemBotName($botUser->email),
        ];
        if ($userBot) {
            $data['clear_day'] = $userBot->clear_day;
            $data['webhook_url'] = $userBot->webhook_url;
            $data['webhook_events'] = $userBot->webhook_events;
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {post} api/users/bot/edit 添加、编辑机器人
     *
     * @apiDescription 需要token身份，编辑 我的机器人 或 管理员修改系统机器人 信息
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName bot__edit
     *
     * @apiParam {Number} [id]          机器人ID（编辑时必填，留空为添加）
     * @apiParam {String} [name]        机器人名称
     * @apiParam {String} [avatar]      机器人头像
     * @apiParam {Number} [session]     开启新会话功能（仅 我的机器人）
     * - 1：开启、0：关闭, 默认：0
     * - 此参数仅在添加机器人时有效
     * - 开启后，机器人对话窗口会出现新会话菜单和历史会话菜单
     * - 开启后，webhook_url 消息会多一个 session_id 字段
     * @apiParam {Number} [clear_day]   清理天数（仅 我的机器人）
     * @apiParam {String} [webhook_url] Webhook地址（仅 我的机器人）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function bot__edit()
    {
        $user = User::auth();
        //
        $botId = intval(Request::input('id'));
        $session = intval(Request::input('session'));
        if (empty($botId)) {
            $res = UserBot::newBot($user->userid, trim(Request::input('name')), (bool)$session);
            if (Base::isError($res)) {
                return $res;
            }
            $botUser = $res['data'];
        } else {
            $botUser = User::whereUserid($botId)->whereBot(1)->first();
            if (empty($botUser)) {
                return Base::retError('机器人不存在');
            }
        }
        //
        $userBot = UserBot::whereBotId($botUser->userid)->whereUserid($user->userid)->first();
        if (empty($userBot)) {
            if (UserBot::systemBotName($botUser->email)) {
                // 系统机器人（仅限管理员）
                if (!$user->isAdmin()) {
                    return Base::retError('权限不足');
                }
            } else {
                // 其他用户的机器人（仅限主人）
                return Base::retError('不是你的机器人');
            }
        }
        //
        $data = Request::input();
        $upUser = [];
        $upBot = [];
        //
        if (Arr::exists($data, 'name')) {
            $upUser['nickname'] = trim($data['name']);
        }
        if (Arr::exists($data, 'avatar')) {
            $avatar = $data['avatar'];
            $avatar = $avatar ? Base::unFillUrl(is_array($avatar) ? $avatar[0]['path'] : $avatar) : '';
            if (str_contains($avatar, 'avatar/')) {
                $avatar = '';
            }
            $upUser['userimg'] = $avatar;
        }
        if (Arr::exists($data, 'clear_day')) {
            $upBot['clear_day'] = min(max(intval($data['clear_day']), 1), 999);
        }
        if (Arr::exists($data, 'webhook_url')) {
            $upBot['webhook_url'] = trim($data['webhook_url']);
        }
        if (Arr::exists($data, 'webhook_events')) {
            $upBot['webhook_events'] = UserBot::normalizeWebhookEvents($data['webhook_events'], false);
        }
        //
        if ($upUser) {
            $botUser->updateInstance($upUser);
            $botUser->save();
        }
        if ($upBot && $userBot) {
            $userBot->updateInstance($upBot);
            $userBot->save();
        }
        //
        $data = [
            'id' => $botUser->userid,
            'name' => $botUser->nickname,
            'avatar' => $botUser->userimg,
            'clear_day' => 0,
            'webhook_url' => '',
            'webhook_events' => [UserBot::WEBHOOK_EVENT_MESSAGE],
            'system_name' => UserBot::systemBotName($botUser->email),
        ];
        if ($userBot) {
            $data['clear_day'] = $userBot->clear_day;
            $data['webhook_url'] = $userBot->webhook_url;
            $data['webhook_events'] = $userBot->webhook_events;
        }
        return Base::retSuccess($botId ? '修改成功' : '添加成功', $data);
    }

    /**
     * @api {get} api/users/bot/delete 删除机器人
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName bot__delete
     *
     * @apiParam {Number} id            机器人ID
     * @apiParam {String} remark        删除备注
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function bot__delete()
    {
        $user = User::auth();
        //
        $botId = intval(Request::input('id'));
        $remark = trim(Request::input('remark'));
        //
        if (empty($remark)) {
            return Base::retError('请输入删除备注');
        }
        if (mb_strlen($remark) > 255) {
            return Base::retError('删除备注长度限制255个字');
        }
        //
        $botUser = User::whereUserid($botId)->whereBot(1)->first();
        if (empty($botUser)) {
            return Base::retError('机器人不存在');
        }
        $userBot = UserBot::whereBotId($botUser->userid)->whereUserid($user->userid)->first();
        if (empty($userBot)) {
            if (UserBot::systemBotName($botUser->email)) {
                // 系统机器人（仅限管理员）
                return Base::retError('系统机器人不能删除');
            } else {
                // 其他用户的机器人（仅限主人）
                return Base::retError('不是你的机器人');
            }
        }
        //
        if (!$botUser->deleteUser($remark)) {
            return Base::retError('删除失败');
        }
        return Base::retSuccess('删除成功');
    }

    /**
     * @api {get} api/users/share/list 获取分享列表
     *
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName share__list
     *
     * @apiParam {String} [type]            分享类型：file-文件，text-列表 默认file
     * @apiParam {String} [key]             搜索关键词（用于搜索会话）
     * @apiParam {Number} [pid]             父级文件id，用于获取子目录和上传到指定目录的id
     * @apiParam {Number} [upload_file_id]  上传文件id
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function share__list()
    {
        $user = User::auth();
        $type = Request::input('type', 'file');
        $key = Request::input('key');
        $pid = intval(Request::input('pid', -1));
        $uploadFileId = intval(Request::input('upload_file_id', -1));
        // 上传文件
        if ($uploadFileId !== -1) {
            if ($pid == -1) $pid = 0;
            $webkitRelativePath = Request::input('webkitRelativePath');
            $data = (new File)->contentUpload($user, $pid, $webkitRelativePath);
            return Base::retSuccess('success', $data);
        }
        // 获取数据
        $lists = [];
        if ($type == 'file' && $pid !== -1) {
            $fileList = (new File)->getFileList($user, $pid, 'dir', false);
            foreach ($fileList as $file) {
                if ($file['id'] != $pid) {
                    $lists[] = [
                        'type' => 'children',
                        'url' => Base::fillUrl("api/users/share/list") . "?pid=" . $file['id'],
                        'icon' => $file['share'] == 1 ? url("images/file/light/folder-share.png") : url("images/file/light/folder.png"),
                        'extend' => ['upload_file_id' => $file['id']],
                        'name' => $file['name'],
                    ];
                }
            }
        } else {
            if ($type == 'file') {
                $lists[] = [
                    'type' => 'children',
                    'url' => Base::fillUrl("api/users/share/list") . "?pid=0",
                    'icon' => url("images/file/light/folder.png"),
                    'extend' => ['upload_file_id' => 0],
                    'name' => Doo::translate('文件'),
                    'sort' => Carbon::parse("9999")->timestamp,
                ];
            }
            $dialogTake = 50;
            $dialogList = WebSocketDialog::searchDialog($user->userid, $key, $dialogTake);
            $dialogIds = [];
            $itemUrl = $type == "file" ? Base::fillUrl("api/dialog/msg/sendfiles") : Base::fillUrl("api/dialog/msg/sendtext");
            foreach ($dialogList as $dialog) {
                if ($dialog['avatar']) {
                    $avatar = url($dialog['avatar']);
                } else if ($dialog['type'] == 'user') {
                    $avatar = User::getAvatar($dialog['dialog_user']['userid'], $dialog['userimg'], $dialog['email'], $dialog['name']);
                } else {
                    $avatar = match ($dialog['group_type']) {
                        'department' => url("images/avatar/default_group_department.png"),
                        'project' => url("images/avatar/default_group_project.png"),
                        'task' => url("images/avatar/default_group_task.png"),
                        default => url("images/avatar/default_group_people.png"),
                    };
                }
                $lists[] = [
                    'type' => 'item',
                    'name' => $dialog['name'],
                    'icon' => $avatar,
                    'url' => $itemUrl,
                    'sort' => Carbon::parse($dialog['last_at'])->timestamp,
                    'extend' => [
                        'dialog_ids' => $dialog['id'],
                        'text_type' => 'text',
                        'reply_id' => 0,
                        'silence' => 'no'
                    ]
                ];
                $dialogIds[] = $dialog['id'];
            }
            if ($key && count($dialogList) < $dialogTake) {
                $dialogUsers = User::select(User::$basicField)
                    ->searchByKeyword($key)
                    ->orderBy('userid')
                    ->take($dialogTake - count($dialogList))
                    ->get();
                foreach ($dialogUsers as $item) {
                    $dialog = WebSocketDialog::getUserDialog($user->userid, $item->userid, now()->addDay());
                    if ($dialog && !in_array($dialog->id, $dialogIds)) {
                        $lists[] = [
                            'type' => 'item',
                            'name' => $item->nickname,
                            'icon' => $item->userimg,
                            'url' => $itemUrl,
                            'sort' => Carbon::parse($item->line_at)->timestamp,
                            'extend' => [
                                'dialog_ids' => $dialog->id,
                                'text_type' => 'text',
                                'reply_id' => 0,
                                'silence' => 'no'
                            ]
                        ];
                        $dialogIds[] = $dialog->id;
                    }
                }
                // 根据 $lists sort 从大到小排序
                usort($lists, function ($a, $b) {
                    return $b['sort'] <=> $a['sort'];
                });
            }
        }
        // 返回
        return Base::retSuccess('success', $lists);
    }

    /**
     * @api {get} api/users/annual/report 年度报告
     *
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName annual__report
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function annual__report()
    {
        $user = User::auth();
        //
        $year = '2023';
        $time = '2300-01-01 00:00:01';
        $prefix = \DB::getTablePrefix();
        $hireTimestamp = strtotime($user->created_at);
        DB::statement("SET SQL_MODE=''");

        // 我的任务
        $taskDb = DB::table('project_tasks as t')
            ->join('project_task_users as tu', 't.id', '=', 'tu.task_id')
            ->where('tu.owner', 1)
            ->whereYear('t.created_at', $year)
            ->where('tu.userid', $user->userid);

        // 我的任务 - 时长（分钟）
        $durationTaskDb = $taskDb->clone()
            ->selectRaw("
                    {$prefix}t.id,
                    {$prefix}t.flow_item_name,
                    {$prefix}t.name as task_name,
                    {$prefix}p.name as project_name,
                    {$prefix}c.name as project_column_name,
                    {$prefix}t.start_at,
                    {$prefix}t.end_at,
                    {$prefix}t.complete_at,
                    {$prefix}t.created_at,
                    ifnull(TIMESTAMPDIFF(MINUTE, {$prefix}t.start_at, {$prefix}t.complete_at), 0) as duration
                ")
            ->leftJoin('projects as p', 'p.id', '=', 't.project_id')
            ->leftJoin('project_columns as c', 'c.id', '=', 't.column_id')
            ->whereNotNull('t.start_at')
            ->whereNotNull('t.complete_at');

        // 最多聊天用户
        $longestChat = DB::table('web_socket_dialogs as d')
            ->selectRaw("
                    {$prefix}d.id,
                    {$prefix}d.name as dialog_name,
                    {$prefix}d.type as dialog_type,
                    {$prefix}d.group_type as dialog_group_type,
                    {$prefix}m.chat_num,
                    {$prefix}u.userid,
                    {$prefix}u.email as user_email,
                    {$prefix}u.nickname as user_nickname,
                    ifnull({$prefix}d.avatar, {$prefix}u.userimg) as avatar
                ")
            ->leftJoinSub(function ($query) use ($user, $year) {
                $query->select('web_socket_dialog_msgs.dialog_id', DB::raw('count(*) as chat_num'))
                    ->from('web_socket_dialog_msgs')
                    ->where('web_socket_dialog_msgs.userid', $user->userid)
                    ->whereYear('web_socket_dialog_msgs.created_at', $year)
                    ->groupBy('web_socket_dialog_msgs.dialog_id');
            }, 'm', 'm.dialog_id', '=', 'd.id')
            ->leftJoin('web_socket_dialog_users as du', function ($query) use ($user) {
                $query->on('d.id', '=', 'du.dialog_id');
                $query->where('du.userid', '!=', $user->userid);
                $query->where('d.type', 'user');
            })
            ->leftJoin('users as u', 'du.userid', '=', 'u.userid')
            ->where('d.type', '!=', 'user')
            ->orWhere('u.bot', 0)
            ->orderByDesc('m.chat_num')
            ->first();
        if (!empty($longestChat)) {
            if ($longestChat->avatar) {
                $longestChat->avatar = url($longestChat->avatar);
            } else if ($longestChat->dialog_type == 'user') {
                $longestChat->avatar = User::getAvatar($longestChat->userid, $longestChat->avatar, $longestChat->user_email, $longestChat->user_nickname);
            } else {
                $longestChat->avatar = match ($longestChat->dialog_group_type) {
                    'department' => url("images/avatar/default_group_department.png"),
                    'project' => url("images/avatar/default_group_project.png"),
                    'task' => url("images/avatar/default_group_task.png"),
                    default => url("images/avatar/default_group_people.png"),
                };
            }
        }

        // 最晚在线时间
        $timezone = config('app.timezone');
        $latestOnline = UserCheckinRecord::whereUserid($user->userid)
            ->whereYear(DB::raw('from_unixtime(report_time)'), $year)
            ->orderByRaw("TIME_FORMAT(DATE_ADD(CONVERT_TZ(from_unixtime(report_time), 'UTC', '$timezone'), INTERVAL 18 HOUR), '%H%i%s') desc")
            ->first();

        //
        $data = [
            // 本人信息
            'user' => [
                'userid' => $user->userid,
                'email' => $user->email,
                'nickname' => $user->nickname,
                'avatar' => User::getAvatar($user->userid, $user->userimg, $user->email, $user->nickname)
            ],
            // 入职时间（年月日）
            'hire_date' => date("Y-m-d", $hireTimestamp),
            // 在职时间（天为单位）
            'tenure_days' => floor((strtotime(date('Y-m-d')) - $hireTimestamp) / (24 * 60 * 60)),
            // 最晚在线时间
            'latest_online_time' => date("Y-m-d H:i:s", $latestOnline->report_time),
            // 跟谁聊天最多（发消息的次数。可以是群、私聊、机器人除外）
            'longest_chat_user' => $longestChat,
            // 跟所有ai机器人聊天的次数
            'chat_al_num' => DB::table('web_socket_dialog_msgs as m')
                ->join('web_socket_dialogs as d', 'd.id', '=', 'm.dialog_id')
                ->join('web_socket_dialog_users as du', 'd.id', '=', 'du.dialog_id')
                ->join('users as u', 'du.userid', '=', 'u.userid')
                ->where('u.email', 'like', "%ai-%")
                ->where('u.bot', 1)
                ->where('m.userid', $user->userid)
                ->whereYear('m.created_at', $year)
                ->count(),
            // 文件创建数量
            'file_created_num' => File::whereCreatedId($user->userid)->whereYear('created_at', $year)->count(),
            // 参与过的项目
            'projects' => DB::table('projects as p')
                ->select('p.id', 'p.name')
                ->join('project_users as pu', 'p.id', '=', 'pu.project_id')
                ->join('project_task_users as ptu', 'p.id', '=', 'ptu.project_id')
                ->where(function($query) use ($user,$year) {
                    $query->where('pu.userid', $user->userid);
                    $query->whereYear('pu.created_at', $year);
                })
                ->orWhere(function($query) use ($user,$year) {
                    $query->where('ptu.userid', $user->userid);
                    $query->whereYear('ptu.created_at', $year);
                })
                ->groupBy('p.id')
                ->take(100)
                ->get(),
            // 任务统计
            'tasks' => [
                // 总数量
                'total' => $taskDb->count(),
                // 完成数量
                'completed' => $taskDb->clone()->whereNotNUll('t.complete_at')->count(),
                // 超时数量
                'overtime' => $taskDb->clone()->whereRaw("ifnull({$prefix}t.complete_at,'$time') > ifnull({$prefix}t.end_at,'$time')")->count(),
                // 做得最久的任务
                'longest_task' => $durationTaskDb->clone()->orderByDesc('duration')->first(),
                // 做得最快的任务
                'fastest_task' => $durationTaskDb->clone()->orderBy('duration')->first(),
                // 每个月完成多少个任务
                'month_completed_task' => $taskDb->clone()
                    ->selectRaw("MONTH({$prefix}t.complete_at) AS month, COUNT({$prefix}t.id) AS num")
                    ->whereNotNUll('t.complete_at')
                    ->whereYear('t.complete_at', $year)
                    ->groupBy('month')
                    ->get()
            ]
        ];
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/users/device/list 获取设备列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName device__list
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function device__list()
    {
        $user = User::auth();
        //
        $list = UserDevice::whereUserid($user->userid)->orderByDesc('id')->take(UserDevice::$deviceLimit)->get();
        //
        return Base::retSuccess('success', [
            'list' => $list
        ]);
    }

    /**
     * @api {get} api/users/device/logout 登出设备（删除设备）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName device__logout
     *
     * @apiParam {Number} id             设备id
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function device__logout()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        if (empty($id)) {
            return Base::retError('参数错误');
        }
        $userDevice = UserDevice::whereUserid($user->userid)->whereId($id)->first();
        if (empty($userDevice)) {
            return Base::retError('设备不存在或已被删除');
        }
        UserDevice::forget($userDevice->id);
        //
        return Base::retSuccess('操作成功');
    }

    /**
     * @api {get} api/users/device/edit 编辑设备
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName device__edit
     *
     * @apiParam {Object} detail                    设备信息
     * @apiParam {String} detail.device_name        设备名称
     * @apiParam {String} detail.app_brand          设备品牌
     * @apiParam {String} detail.app_model          设备型号
     * @apiParam {String} detail.app_os             设备操作系统
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function device__edit()
    {
        User::auth();
        //
        $detail = Request::input();
        $detail = array_intersect_key($detail, array_flip([ 'device_name', 'app_brand', 'app_model','app_os']));
        if (empty($detail)) {
            return Base::retError('参数错误');
        }
        //
        $row = UserDevice::record();
        if (empty($row)) {
            return Base::retError('设备不存在或已被删除');
        }
        $deviceInfo = array_merge(Base::json2array($row->detail), $detail);
        $row->detail = Base::array2json($deviceInfo);
        $row->save();
        //
        return Base::retSuccess('保存成功');
    }

    /**
     * @api {get} api/users/task/browse 获取任务浏览历史
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName task__browse
     *
     * @apiParam {Number} [limit=20]            获取数量限制，最大50
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__browse()
    {
        $user = User::auth();
        //
        $limit = min(intval(Request::input('limit', 20)), 50);
        //
        $browseHistory = UserTaskBrowse::getUserBrowseHistory($user->userid, $limit);

        $data = [];
        foreach ($browseHistory as $browse) {
            if ($browse->task) {
                // 解析 flow_item_name 字段（格式：status|name|color）
                $flowItemParts = explode('|', $browse->task->flow_item_name ?: '');
                $flowItemStatus = $flowItemParts[0] ?? '';
                $flowItemName = $flowItemParts[1] ?? $browse->task->flow_item_name;
                $flowItemColor = $flowItemParts[2] ?? '';

                $data[] = [
                    'id' => $browse->task->id,
                    'name' => $browse->task->name,
                    'project_id' => $browse->task->project_id,
                    'column_id' => $browse->task->column_id,
                    'parent_id' => $browse->task->parent_id,
                    'flow_item_id' => $browse->task->flow_item_id,
                    'flow_item_name' => $flowItemName,
                    'flow_item_status' => $flowItemStatus,
                    'flow_item_color' => $flowItemColor,
                    'complete_at' => $browse->task->complete_at,
                    'browsed_at' => $browse->browsed_at,
                ];
            }
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/users/task/browse_save 记录任务浏览历史
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName task__browse_save
     *
     * @apiParam {Number} task_id               任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__browse_save()
    {
        $user = User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        if ($task_id <= 0) {
            return Base::retError('参数错误');
        }
        //
        ProjectTask::userTask($task_id, null, null);
        //
        UserTaskBrowse::recordBrowse($user->userid, $task_id);
        //
        return Base::retSuccess('记录成功');
    }

    /**
     * @api {post} api/users/task/browse_clean 清理任务浏览历史
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName task__browse_clean
     *
     * @apiParam {Number} [keep_count=100]      保留记录数量，0表示全部清理
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__browse_clean()
    {
        $user = User::auth();
        //
        $keepCount = intval(Request::input('keep_count', 100));
        //
        $deletedCount = UserTaskBrowse::cleanUserBrowseHistory($user->userid, $keepCount);
        //
        return Base::retSuccess('清理完成', ['deleted_count' => $deletedCount]);
    }

    /**
     * @api {get} api/users/recent/browse 获取最近访问记录
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName recent__browse
     *
     * @apiParam {String} [type]                类型过滤 (task/file/task_file/message_file)
     * @apiParam {Number} [page=1]              页码
     * @apiParam {Number} [page_size=20]        每页数量，最大100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function recent__browse()
    {
        $user = User::auth();

        $type = trim(Request::input('type'));
        $page = max(1, intval(Request::input('page', 1)));
        $pageSize = intval(Request::input('page_size', 20));
        $pageSize = max(1, min(100, $pageSize));

        $query = UserRecentItem::whereUserid($user->userid);
        if ($type !== '') {
            $query->where('target_type', $type);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('browsed_at')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        $taskIds = [];
        $fileIds = [];
        $taskFileIds = [];
        $messageIds = [];

        foreach ($items as $item) {
            switch ($item->target_type) {
                case UserRecentItem::TYPE_TASK:
                    $taskIds[] = $item->target_id;
                    break;
                case UserRecentItem::TYPE_FILE:
                    $fileIds[] = $item->target_id;
                    break;
                case UserRecentItem::TYPE_TASK_FILE:
                    $taskFileIds[] = $item->target_id;
                    break;
                case UserRecentItem::TYPE_MESSAGE_FILE:
                    $messageIds[] = $item->target_id;
                    break;
            }
        }

        $tasks = empty($taskIds) ? collect() : ProjectTask::with(['project'])
            ->whereIn('id', array_unique($taskIds))
            ->whereNull('archived_at')
            ->get()
            ->keyBy('id');

        $files = empty($fileIds) ? collect() : File::whereIn('id', array_unique($fileIds))
            ->get()
            ->keyBy('id');

        $taskFiles = empty($taskFileIds) ? collect() : ProjectTaskFile::whereIn('id', array_unique($taskFileIds))
            ->get()
            ->keyBy('id');

        $taskFileTaskIds = $taskFiles->pluck('task_id')->filter()->unique()->all();
        $taskFileTasks = empty($taskFileTaskIds) ? collect() : ProjectTask::whereIn('id', $taskFileTaskIds)
            ->get()
            ->keyBy('id');

        $projectIds = $tasks->pluck('project_id')
            ->merge($taskFiles->pluck('project_id'))
            ->filter()
            ->unique()
            ->all();

        $projects = empty($projectIds) ? collect() : Project::whereIn('id', $projectIds)
            ->get()
            ->keyBy('id');

        $messages = empty($messageIds) ? collect() : WebSocketDialogMsg::whereIn('id', array_unique($messageIds))
            ->get()
            ->keyBy('id');

        $dialogIds = $messages->pluck('dialog_id')->filter()->unique()->all();
        $dialogs = empty($dialogIds) ? collect() : WebSocketDialog::whereIn('id', $dialogIds)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($items as $item) {
            $timestamp = $item->browsed_at ?: $item->updated_at;
            if ($timestamp instanceof Carbon) {
                $browsedAt = $timestamp->toDateTimeString();
            } elseif ($timestamp) {
                $browsedAt = Carbon::parse($timestamp)->toDateTimeString();
            } else {
                $browsedAt = Carbon::now()->toDateTimeString();
            }

            $baseData = [
                'record_id' => $item->id,
                'source_type' => $item->source_type,
                'source_id' => $item->source_id,
                'browsed_at' => $browsedAt,
            ];

            switch ($item->target_type) {
                case UserRecentItem::TYPE_TASK:
                    $task = $tasks->get($item->target_id);
                    if (!$task) {
                        continue 2;
                    }
                    $flowItemParts = explode('|', $task->flow_item_name ?: '');
                    $flowItemName = $flowItemParts[1] ?? $task->flow_item_name;
                    $flowItemStatus = $flowItemParts[0] ?? '';
                    $flowItemColor = $flowItemParts[2] ?? '';
                    $result[] = array_merge($baseData, [
                        'type' => UserRecentItem::TYPE_TASK,
                        'id' => $task->id,
                        'name' => $task->name,
                        'project_id' => $task->project_id,
                        'project_name' => $task->project->name ?? '',
                        'column_id' => $task->column_id,
                        'flow_item_id' => $task->flow_item_id,
                        'flow_item_name' => $flowItemName,
                        'flow_item_status' => $flowItemStatus,
                        'flow_item_color' => $flowItemColor,
                        'complete_at' => $task->complete_at,
                    ]);
                    break;

                case UserRecentItem::TYPE_FILE:
                    $file = $files->get($item->target_id);
                    if (!$file) {
                        continue 2;
                    }
                    $result[] = array_merge($baseData, [
                        'type' => UserRecentItem::TYPE_FILE,
                        'id' => $file->id,
                        'name' => $file->name,
                        'ext' => $file->ext,
                        'size' => (int) $file->size,
                        'file_type' => $file->type,
                        'folder_id' => (int) $file->pid,
                    ]);
                    break;

                case UserRecentItem::TYPE_TASK_FILE:
                    $taskFile = $taskFiles->get($item->target_id);
                    if (!$taskFile) {
                        continue 2;
                    }
                    $project = $projects->get($taskFile->project_id);
                    $taskInfo = $taskFileTasks->get($taskFile->task_id);
                    $result[] = array_merge($baseData, [
                        'type' => UserRecentItem::TYPE_TASK_FILE,
                        'id' => $taskFile->id,
                        'name' => $taskFile->name,
                        'ext' => $taskFile->ext,
                        'size' => (int) $taskFile->size,
                        'task_id' => $taskFile->task_id,
                        'task_name' => $taskInfo->name ?? '',
                        'project_id' => $taskFile->project_id,
                        'project_name' => $project->name ?? '',
                    ]);
                    break;

                case UserRecentItem::TYPE_MESSAGE_FILE:
                    $message = $messages->get($item->target_id);
                    if (!$message || $message->type !== 'file') {
                        continue 2;
                    }
                    $msgData = Base::json2array($message->getRawOriginal('msg'));
                    $dialog = $dialogs->get($message->dialog_id);
                    $result[] = array_merge($baseData, [
                        'type' => UserRecentItem::TYPE_MESSAGE_FILE,
                        'id' => $message->id,
                        'name' => $msgData['name'] ?? '',
                        'ext' => $msgData['ext'] ?? '',
                        'size' => isset($msgData['size']) ? (int) $msgData['size'] : 0,
                        'dialog_id' => $message->dialog_id,
                        'dialog_name' => $dialog->name ?? '',
                    ]);
                    break;
            }
        }

        return Base::retSuccess('success', [
            'list' => $result,
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    /**
     * @api {post} api/users/recent/delete 删除最近访问记录
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName recent__delete
     *
     * @apiParam {Number} id                      记录ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function recent__delete()
    {
        $user = User::auth();

        $id = intval(Request::input('id'));
        if ($id <= 0) {
            return Base::retError('参数错误');
        }

        $record = UserRecentItem::whereUserid($user->userid)->whereId($id)->first();
        if (!$record) {
            return Base::retError('记录不存在');
        }

        $record->delete();

        return Base::retSuccess('删除成功');
    }

    /**
     * @api {get} api/users/appsort 获取个人应用排序
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName appsort
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function appsort()
    {
        $user = User::auth();
        $sorts = UserAppSort::getSorts($user->userid);
        return Base::retSuccess('success', [
            'sorts' => $sorts,
        ]);
    }

    /**
     * @api {post} api/users/appsort/save 保存个人应用排序
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName appsort__save
     *
     * @apiParam {Object} sorts                排序配置，示例：{"base":["micro:calendar"],"admin":["system:ldap"]}
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function appsort__save()
    {
        $user = User::auth();
        $sorts = UserAppSort::normalizeSorts(Request::input('sorts'));
        $record = UserAppSort::saveSorts($user->userid, $sorts);
        return Base::retSuccess('保存成功', [
            'sorts' => $record->sorts ?? $sorts,
        ]);
    }

    /**
     * @api {get} api/users/favorites 获取用户收藏列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName favorites
     *
     * @apiParam {String} [type]               收藏类型过滤 (task/project/file/message)
     * @apiParam {Number} [page=1]             页码
     * @apiParam {Number} [pagesize=20]        每页数量
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function favorites()
    {
        $user = User::auth();
        //
        $type = Request::input('type');
        $page = intval(Request::input('page', 1));
        $pageSize = min(intval(Request::input('pagesize', 20)), 100);
        //
        // 验证收藏类型
        $allowedTypes = [UserFavorite::TYPE_TASK, UserFavorite::TYPE_PROJECT, UserFavorite::TYPE_FILE, UserFavorite::TYPE_MESSAGE];
        if ($type && !in_array($type, $allowedTypes)) {
            return Base::retError('无效的收藏类型');
        }
        //
        $result = UserFavorite::getUserFavorites($user->userid, $type, $page, $pageSize);
        //
        return Base::retSuccess('success', $result);
    }

    /**
     * @api {post} api/users/favorite/toggle 切换收藏状态
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName favorite__toggle
     *
     * @apiParam {String} type                  收藏类型 (task/project/file/message)
     * @apiParam {Number} id                    收藏对象ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function favorite__toggle()
    {
        $user = User::auth();
        //
        $type = trim(Request::input('type'));
        $id = intval(Request::input('id'));
        //
        if (!$type || $id <= 0) {
            return Base::retError('参数错误');
        }
        //
        // 验证收藏类型
        $allowedTypes = [UserFavorite::TYPE_TASK, UserFavorite::TYPE_PROJECT, UserFavorite::TYPE_FILE, UserFavorite::TYPE_MESSAGE];
        if (!in_array($type, $allowedTypes)) {
            return Base::retError('无效的收藏类型');
        }
        //
        // 验证对象是否存在（简化验证，实际应该加上权限检查）
        switch ($type) {
            case UserFavorite::TYPE_TASK:
                $object = ProjectTask::whereId($id)->first();
                if (!$object) {
                    return Base::retError('任务不存在');
                }
                break;
            case UserFavorite::TYPE_PROJECT:
                $object = Project::whereId($id)->first();
                if (!$object) {
                    return Base::retError('项目不存在');
                }
                break;
            case UserFavorite::TYPE_FILE:
                $object = File::whereId($id)->first();
                if (!$object) {
                    return Base::retError('文件不存在');
                }
                break;
            case UserFavorite::TYPE_MESSAGE:
                $object = WebSocketDialogMsg::whereId($id)->first();
                if (!$object) {
                    return Base::retError('消息不存在');
                }
                break;
        }
        //
        $result = UserFavorite::toggleFavorite($user->userid, $type, $id);
        //
        $message = $result['favorited'] ? '收藏成功' : '取消收藏成功';
        return Base::retSuccess($message, $result);
    }

    /**
     * @api {post} api/users/favorite/remark 修改收藏备注
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName favorite__remark
     *
     * @apiParam {String} type                  收藏类型 (task/project/file/message)
     * @apiParam {Number} id                    收藏对象ID
     * @apiParam {String} remark                收藏备注（<=255个字符）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function favorite__remark()
    {
        $user = User::auth();
        //
        $type = trim(Request::input('type'));
        $id = intval(Request::input('id'));
        $remark = trim(Request::input('remark', ''));

        if (!$type || $id <= 0) {
            return Base::retError('参数错误');
        }

        $allowedTypes = [UserFavorite::TYPE_TASK, UserFavorite::TYPE_PROJECT, UserFavorite::TYPE_FILE, UserFavorite::TYPE_MESSAGE];
        if (!in_array($type, $allowedTypes)) {
            return Base::retError('无效的收藏类型');
        }

        if ($remark === '') {
            return Base::retError('请输入修改备注');
        }

        if (mb_strlen($remark) > 255) {
            return Base::retError('备注最多支持255个字符');
        }

        $favorite = UserFavorite::updateRemark($user->userid, $type, $id, $remark);

        if (!$favorite) {
            return Base::retError('收藏记录不存在');
        }

        return Base::retSuccess('修改备注成功', [
            'remark' => $favorite->remark,
        ]);
    }

    /**
     * @api {post} api/users/favorites/clean 清理用户收藏
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName favorites__clean
     *
     * @apiParam {String} [type]                收藏类型 (task/project/file/message)，不传则清理全部
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function favorites__clean()
    {
        $user = User::auth();
        //
        $type = trim(Request::input('type'));
        //
        // 验证收藏类型
        if ($type) {
            $allowedTypes = [UserFavorite::TYPE_TASK, UserFavorite::TYPE_PROJECT, UserFavorite::TYPE_FILE, UserFavorite::TYPE_MESSAGE];
            if (!in_array($type, $allowedTypes)) {
                return Base::retError('无效的收藏类型');
            }
        }
        //
        $deletedCount = UserFavorite::cleanUserFavorites($user->userid, $type);
        //
        $message = $type ? "清理{$type}收藏成功" : '清理全部收藏成功';
        return Base::retSuccess($message, ['deleted_count' => $deletedCount]);
    }

    /**
     * @api {get} api/users/favorite/check 检查收藏状态
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName favorite__check
     *
     * @apiParam {String} type                  收藏类型 (task/project/file/message)
     * @apiParam {Number} id                    收藏对象ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function favorite__check()
    {
        $user = User::auth();
        //
        $type = trim(Request::input('type'));
        $id = intval(Request::input('id'));
        //
        if (!$type || $id <= 0) {
            return Base::retError('参数错误');
        }
        //
        // 验证收藏类型
        $allowedTypes = [UserFavorite::TYPE_TASK, UserFavorite::TYPE_PROJECT, UserFavorite::TYPE_FILE, UserFavorite::TYPE_MESSAGE];
        if (!in_array($type, $allowedTypes)) {
            return Base::retError('无效的收藏类型');
        }
        //
        $isFavorited = UserFavorite::isFavorited($user->userid, $type, $id);
        //
        return Base::retSuccess('success', ['favorited' => $isFavorited]);
    }

}
