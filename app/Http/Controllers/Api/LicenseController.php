<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Base;
use App\Module\OnlineLicense;
use Request;

/**
 * 在线授权客户端（与 SystemController::license 的离线粘贴并存）。
 *
 * 动态路由（routes/web.php）：
 *   api/license/login        -> login()
 *   api/license/trial/send   -> trial__send()
 *   api/license/trial        -> trial()
 *   api/license/status       -> status()
 *   api/license/refresh      -> refresh()
 *   api/license/logout       -> logout()
 */
class LicenseController extends AbstractController
{
    /**
     * 账号登录并签发在线授权
     */
    public function login()
    {
        User::auth('admin');
        $account = trim(Request::input('account'));
        $password = trim(Request::input('password'));
        if ($account === '' || $password === '') {
            return Base::retError('请输入账号和密码');
        }
        $data = OnlineLicense::login($account, $password);
        return Base::retSuccess('授权成功', $data);
    }

    /**
     * 发送试用验证码
     */
    public function trial__send()
    {
        User::auth('admin');
        $account = trim(Request::input('account'));
        $password = trim(Request::input('password'));
        if ($account === '' || $password === '') {
            return Base::retError('请输入账号和密码');
        }
        $email = OnlineLicense::trialSend($account, $password);
        return Base::retSuccess('验证码已发送', ['email' => $email]);
    }

    /**
     * 申请试用并签发
     */
    public function trial()
    {
        User::auth('admin');
        $account = trim(Request::input('account'));
        $password = trim(Request::input('password'));
        $code = trim(Request::input('code'));
        if ($account === '' || $password === '' || $code === '') {
            return Base::retError('请输入账号、密码和验证码');
        }
        $data = OnlineLicense::trial($account, $password, $code);
        return Base::retSuccess('试用已开通', $data);
    }

    /**
     * 当前在线授权状态
     */
    public function status()
    {
        User::auth('admin');
        return Base::retSuccess('success', OnlineLicense::status());
    }

    /**
     * 进入授权页时的静默刷新：服务可达则更新授权数据，网络失败则不更新、不提示。
     */
    public function refresh()
    {
        User::auth('admin');
        OnlineLicense::refresh();
        return Base::retSuccess('success', OnlineLicense::status());
    }

    /**
     * 退出在线授权（释放座位 + 回落默认）
     */
    public function logout()
    {
        User::auth('admin');
        OnlineLicense::logout();
        return Base::retSuccess('已退出在线授权');
    }
}
