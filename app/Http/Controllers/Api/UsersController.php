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
use App\Ldap\LdapUser;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\UserBot;
use App\Models\WebSocket;
use App\Models\UmengAlias;
use App\Models\UserDelete;
use App\Models\UserTransfer;
use App\Models\AbstractModel;
use App\Models\UserCheckinFace;
use App\Models\UserCheckinMac;
use App\Models\UserDepartment;
use App\Models\WebSocketDialog;
use App\Models\UserCheckinRecord;
use App\Models\WebSocketDialogMsg;
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
     * @api {get} api/users/login          01. 登录、注册
     *
     * @apiDescription 需要token身份
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
            if (in_array('disable', $user->identity)) {
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
                'name' => Doo::translate('个人项目'),
                'desc' => Doo::translate('注册时系统自动创建项目，你可以自由删除。'),
                'personal' => 1,
            ], $user->userid);
        }
        //
        return Base::retSuccess($type == 'reg' ? "注册成功" : "登录成功", $user);
    }

    /**
     * @api {get} api/users/login/qrcode          02. 二维码登录
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
     * @api {get} api/users/login/needcode          03. 是否需要验证码
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
     * @api {get} api/users/login/codeimg          04. 验证码图片
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
     * @api {get} api/users/login/codejson          05. 验证码json
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
     * @api {get} api/users/reg/needinvite          06. 是否需要邀请码
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
     * @api {get} api/users/info          07. 获取我的信息
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
     * @api {get} api/users/editdata          08. 修改自己的资料
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
     * @api {get} api/users/editpass          09. 修改自己的密码
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
     * @api {get} api/users/search          10. 搜索会员列表
     *
     * @apiDescription 搜索会员列表
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName searchinfo
     *
     * @apiParam {Object} keys          搜索条件
     * - keys.key                           昵称、拼音、邮箱关键字
     * - keys.disable                       0-排除离职（默认），1-仅离职，2-含离职
     * - keys.bot                           0-排除机器人（默认），1-仅机器人，2-含机器人
     * - keys.project_id                    在指定项目ID
     * - keys.no_project_id                 不在指定项目ID
     * - keys.dialog_id                     在指定对话ID
     * @apiParam {Object} sorts         排序方式
     * - sorts.az                           按字母：asc|desc
     * @apiParam {Number} updated_time  在这个时间戳之后更新的
     * @apiParam {Number} state         获取在线状态
     * - 0: 不获取（默认）
     * - 1: 获取会员在线状态，返回数据多一个online值
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
        $keys = is_array($keys) ? $keys : [];
        $sorts = is_array($sorts) ? $sorts : [];
        //
        if ($keys['key']) {
            if (str_contains($keys['key'], "@")) {
                $builder->where("email", "like", "%{$keys['key']}%");
            } else {
                $builder->where(function($query) use ($keys) {
                    $query->where("nickname", "like", "%{$keys['key']}%")
                        ->orWhere("pinyin", "like", "%{$keys['key']}%");
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
        $list->transform(function (User $userInfo) use ($user, $state) {
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
            return $userInfo;
        });
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/users/basic          11. 获取指定会员基础信息
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
                //
                $retArray[] = $basic;
            }
        }
        return Base::retSuccess('success', $retArray);
    }

    /**
     * @api {get} api/users/lists          12. 会员列表（限管理员）
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
     * @api {get} api/users/operation          13. 操作会员（限管理员）
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
                $faceimg = $data['checkin_face'] ? $data['checkin_face'] : '';

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
                $transferUserid = is_array($data['transfer_userid']) ? $data['transfer_userid'][0] : $data['transfer_userid'];
                $transferUser = User::find(intval($transferUserid));
                if (empty($transferUser)) {
                    return Base::retError('请选择正确的交接人');
                }
                if ($transferUser->userid === $userInfo->userid) {
                    return Base::retError('不能移交给自己');
                }
                if (in_array('disable', $transferUser->identity)) {
                    return Base::retError('交接人已离职，请选择另一个交接人');
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
                } elseif ($type === 'setdisable') {
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
     * @api {get} api/users/email/verification          14. 邮箱验证
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
        $time = Base::Time();

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
     * @api {get} api/users/umeng/alias          15. 设置友盟别名
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName umeng__alias
     *
     * @apiParam {String} alias           别名
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function umeng__alias()
    {
        $data = Request::input();
        // 表单验证
        Base::validator($data, [
            'alias.required' => '别名不能为空',
            'alias.between:2,20' => '别名的长度在2-20个字符',
        ]);
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
        $row = UmengAlias::where($inArray);
        if ($row->exists()) {
            $row->update([
                'ua' => $data['userAgent'],
                'device' => $data['deviceModel'],
                'updated_at' => Carbon::now()
            ]);
            return Base::retSuccess('别名已存在');
        }
        $row = UmengAlias::createInstance(array_merge($inArray, [
            'ua' => $data['userAgent'],
            'device' => $data['deviceModel'],
        ]));
        if ($row->save()) {
            return Base::retSuccess('添加成功');
        } else {
            return Base::retError('添加错误');
        }
    }

    /**
     * @api {get} api/users/meeting/open          16. 【会议】创建会议、加入会议
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
     * @api {get} api/users/meeting/link          17. 【会议】获取分享链接
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
     * @api {get} api/users/meeting/tourist          18. 【会议】游客信息
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
     * @api {get} api/users/meeting/invitation          19. 【会议】发送邀请
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
     * @api {get} api/users/email/send          20. 发送邮箱验证码
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
     * @api {get} api/users/email/edit          21. 修改邮箱
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
     * @api {get} api/users/delete/account          22. 删除帐号
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
     * @api {get} api/users/department/list          23. 部门列表（限管理员）
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
     * @api {get} api/users/department/add          24. 新建、修改部门（限管理员）
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
            if ($parentDepartment->parent_id > 0) {
                return Base::retError('上级部门层级错误');
            }
            if (UserDepartment::whereParentId($parent_id)->count() > 20) {
                return Base::retError('每个部门最多只能创建20个子部门');
            }
            if ($id > 0 && UserDepartment::whereParentId($id)->exists()) {
                return Base::retError('含有子部门无法修改上级部门');
            }
        }
        if (empty($owner_userid) || !User::whereUserid($owner_userid)->exists()) {
            return Base::retError('请选择正确的部门负责人');
        }
        //
        $userDepartment->saveDepartment([
            'name' => $name,
            'parent_id' => $parent_id,
            'owner_userid' => $owner_userid,
        ], $dialog_useid);
        Cache::forever("UserDepartment::rand", Base::generatePassword());
        //
        return Base::retSuccess($parent_id > 0 ? '保存成功' : '新建成功');
    }

    /**
     * @api {get} api/users/department/del          25. 删除部门（限管理员）
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
        $userDepartment->deleteDepartment();
        Cache::forever("UserDepartment::rand", Base::generatePassword());
        //
        return Base::retSuccess('删除成功');
    }

    /**
     * @api {get} api/users/checkin/get          26. 获取签到设置
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
     * @api {post} api/users/checkin/save          27. 保存签到设置
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
     * @api {get} api/users/checkin/list          28. 获取签到数据
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
     * @api {get} api/users/socket/status          29. 获取socket状态
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
     * @api {get} api/users/key/client          30. 客户端KEY
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
     * @api {get} api/users/bot/info          31. 机器人信息
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
            'system_name' => UserBot::systemBotName($botUser->email),
        ];
        if ($userBot) {
            $data['clear_day'] = $userBot->clear_day;
            $data['webhook_url'] = $userBot->webhook_url;
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {post} api/users/bot/edit          32. 编辑机器人
     *
     * @apiDescription 需要token身份，编辑 我的机器人 或 管理员修改系统机器人 信息
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName bot__edit
     *
     * @apiParam {Number} id            机器人ID
     * @apiParam {String} [name]        机器人名称
     * @apiParam {String} [avatar]      机器人头像
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
        $data = Request::input();
        $upUser = [];
        $upBot = [];
        //
        if (isset($data['name'])) {
            $upUser['nickname'] = trim($data['name']);
        }
        if (isset($data['avatar'])) {
            $avatar = $data['avatar'];
            $avatar = $avatar ? Base::unFillUrl(is_array($avatar) ? $avatar[0]['path'] : $avatar) : '';
            if (str_contains($avatar, 'avatar/')) {
                $avatar = '';
            }
            $upUser['userimg'] = $avatar;
        }
        if (isset($data['clear_day'])) {
            $upBot['clear_day'] = min(max(intval($data['clear_day']), 1), 999);
        }
        if (isset($data['webhook_url'])) {
            $upBot['webhook_url'] = trim($data['webhook_url']);
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
            'system_name' => UserBot::systemBotName($botUser->email),
        ];
        if ($userBot) {
            $data['clear_day'] = $userBot->clear_day;
            $data['webhook_url'] = $userBot->webhook_url;
        }
        return Base::retSuccess('修改成功', $data);
    }

    /**
     * @api {get} api/users/share/list          33. 获取分享列表
     *
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName share__list
     *
     * @apiParam {String} [type]            分享类型：file-文件，text-列表 默认file
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
                ];
            }
            $dialogList = WebSocketDialog::getDialogList($user->userid);
            foreach ($dialogList['data'] as $dialog) {
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
                    'url' => $type == "file" ? Base::fillUrl("api/dialog/msg/sendfiles") : Base::fillUrl("api/dialog/msg/sendtext"),
                    'extend' => [
                        'dialog_ids' => $dialog['id'],
                        'text_type' => 'text',
                        'reply_id' => 0,
                        'silence' => 'no'
                    ]
                ];
            }
        }
        // 返回
        return Base::retSuccess('success', $lists);
    }

    /**
     * @api {get} api/users/annual/report          34. 年度报告
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
        global $_A;
        if (!isset($_A["__annual__report_".$user->userid])) {
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
            $_A["__annual__report_".$user->userid] = [
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
        }
        //
        return Base::retSuccess('success', $_A["__annual__report_".$user->userid]);
    }
}
