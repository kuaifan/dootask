<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Base;
use Cache;
use Captcha;
use Carbon\Carbon;
use Request;

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
     * @apiParam {String} userpass       密码
     * @apiParam {String} [code]         登录验证码
     * @apiParam {String} [key]          登陆验证码key
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据（同"获取我的信息"接口）
     */
    public function login()
    {
        $type = trim(Request::input('type'));
        $email = trim(Request::input('email'));
        $userpass = trim(Request::input('userpass'));
        if ($type == 'reg') {
            $setting = Base::setting('system');
            if ($setting['reg'] == 'close') {
                return Base::retError('未开放注册！');
            }
            $user = User::reg($email, $userpass);
            if (Base::isError($user)) {
                return $user;
            } else {
                $user = User::IDE($user['data']);
            }
        } else {
            $needCode = !Base::isError(User::needCode($email));
            if ($needCode) {
                $code = trim(Request::input('code'));
                $key = trim(Request::input('key'));
                if (empty($code)) {
                    return Base::retError('请输入验证码！', ['code' => 'need']);
                }
                if (empty($key)) {
                    if (!Captcha::check($code)) {
                        return Base::retError('请输入正确的验证码！', ['code' => 'need']);
                    }
                } else {
                    if (!Captcha::check_api($code, $key)) {
                        return Base::retError('请输入正确的验证码！', ['code' => 'need']);
                    }
                }
            }
            //
            $retError = function ($msg) use ($email) {
                Cache::forever("code::" . $email, "need");
                $needCode = !Base::isError(User::needCode($email));
                $needData = [ 'code' => $needCode ? 'need' : 'no' ];
                return Base::retError($msg, $needData);
            };
            $user = User::whereEmail($email)->first();
            if (empty($user)) {
                return $retError('账号或密码错误！');
            }
            if ($user->userpass != Base::md52($userpass, $user->encrypt)) {
                return $retError('账号或密码错误！');
            }
            Cache::forget("code::" . $email);
        }
        //
        $array = [
            'login_num' => $user->login_num + 1,
            'last_ip' => Base::getIp(),
            'last_at' => Carbon::now(),
            'line_ip' => Base::getIp(),
            'line_at' => Carbon::now(),
        ];
        foreach ($array as $key => $value) {
            $user->$key = $value;
        }
        $user->save();
        //
        $user->token = User::token($user);
        return Base::retSuccess($type == 'reg' ? "注册成功" : "登录成功", $user);
    }

    /**
     * @api {get} api/users/login/needcode          02. 是否需要验证码
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
     * @api {get} api/users/login/codeimg          03. 验证码图片
     *
     * @apiDescription 用于判断是否需要登录验证码
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName login__codeimg
     *
     * @apiParam {String} email       用户名
     *
     * @apiSuccess {Image} data     返回数据（直接输出图片）
     */
    public function login__codeimg()
    {
        return Captcha::create();
    }

    /**
     * @api {get} api/users/info          04. 获取我的信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName info
     *
     * @apiParam {String} [callback]           jsonp返回字段
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} data:
    {
        "userid": 1,
        "identity": [ ],
        "az": "",
        "email": "admin@admin.com",
        "nickname": "admin",
        "userimg": "",
        "login_num": 10,
        "changepass": 0,
        "last_ip": "10.22.22.1",
        "last_at": "2021-06-01 12:00:00",
        "line_ip": "10.22.22.1",
        "line_at": "2021-06-01 12:00:00",
        "created_ip": "",
    }
     */
    public function info()
    {
        $callback = Request::input('callback');
        //
        $user = User::authE();
        if (Base::isError($user)) {
            if (strlen($callback) > 3) {
                return $callback . '(' . json_encode($user) . ')';
            }
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        if (strlen($callback) > 3) {
            return $callback . '(' . json_encode(Base::retSuccess('success', $user)) . ')';
        }
        return Base::retSuccess('success', $user);
    }

    /**
     * @api {get} api/users/editdata          05. 修改自己的资料
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName editdata
     *
     * @apiParam {Object} [userimg]             会员头像（地址）
     * @apiParam {String} [nickname]            昵称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据（同"获取我的信息"接口）
     */
    public function editdata()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        //头像
        $userimg = Request::input('userimg');
        if ($userimg) {
            $userimg = is_array($userimg) ? $userimg[0]['path'] : $userimg;
            $user->userimg = Base::unFillUrl($userimg);
        }
        //昵称
        $nickname = trim(Request::input('nickname'));
        if ($nickname) {
            if (mb_strlen($nickname) < 2) {
                return Base::retError('昵称不可以少于2个字！');
            } elseif (mb_strlen($nickname) > 8) {
                return Base::retError('昵称最多只能设置8个字！');
            } else {
                $user->nickname = $nickname;
            }
        }
        //
        $user->save();
        return Base::retSuccess('修改成功！', $user);
    }

    /**
     * @api {get} api/users/editpass          06. 修改自己的密码
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
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $oldpass = trim(Request::input('oldpass'));
        $newpass = trim(Request::input('newpass'));
        if (strlen($newpass) < 6) {
            return Base::retError('密码设置不能小于6位数！');
        } elseif (strlen($newpass) > 32) {
            return Base::retError('密码最多只能设置32位数！');
        }
        if ($oldpass == $newpass) {
            return Base::retError('新旧密码一致！');
        }
        //
        if (env("PASSWORD_ADMIN") == 'disabled') {
            if ($user->userid == 1) {
                return Base::retError('当前环境禁止修改密码！');
            }
        }
        if (env("PASSWORD_OWNER") == 'disabled') {
            return Base::retError('当前环境禁止修改密码！');
        }
        //
        $verify = User::whereUserid($user->userid)->whereUserpass(Base::md52($oldpass, User::token2encrypt()))->count();
        if (empty($verify)) {
            return Base::retError('请填写正确的旧密码！');
        }
        //
        $user->encrypt = Base::generatePassword(6);
        $user->userpass = Base::md52($newpass, $user->encrypt);
        $user->changepass = 0;
        $user->save();
        return Base::retSuccess('修改成功！', $user);
    }

    /**
     * @api {get} api/users/login/codejson          07. 验证码json
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
     * @api {get} api/users/search          08. 搜索会员列表
     *
     * @apiDescription 搜索会员列表
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName searchinfo
     *
     * @apiParam {Object} where            搜索条件
     * - where.key  昵称、邮箱、用户名
     * @apiParam {Number} [take]           获取数量，10-100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function search()
    {
        $builder = User::select(['userid', 'email', 'nickname', 'userimg']);
        //
        $keys = Request::input('where');
        if (is_array($keys)) {
            if ($keys['key']) {
                $builder->where(function($query) use ($keys) {
                    $query->where('email', 'like', '%,' . $keys['key'] . ',%')
                        ->orWhere('nickname', 'like', '%,' . $keys['key'] . ',%');
                });
            }
        }
        //
        $list = $builder->orderBy('userid')->take(Base::getPaginate(100, 10, 'take'))->get();
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/users/basic          09. 获取指定会员基本信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName basic
     *
     * @apiParam {Number} userid          会员ID(多个格式：jsonArray，一次最多30个)
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function basic()
    {
        $userid = Request::input('userid');
        $array = Base::json2array($userid);
        if (empty($array)) {
            $array[] = $userid;
        }
        if (count($array) > 50) {
            return Base::retError(['一次最多只能获取%条数据！', 50]);
        }
        $retArray = [];
        foreach ($array AS $id) {
            $basic = User::userid2basic($id);
            if ($basic) {
                $retArray[] = $basic;
            }
        }
        return Base::retSuccess('success', $retArray);
    }
}
