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
 *   api/license/email/send   -> email__send()
 *   api/license/login        -> login()
 *   api/license/login/confirm -> login__confirm()
 *   api/license/trial        -> trial()
 *   api/license/status       -> status()
 *   api/license/refresh      -> refresh()
 *   api/license/logout       -> logout()
 */
class LicenseController extends AbstractController
{
    /**
     * 发送邮箱验证码（登录与试用共用）
     */
    public function email__send()
    {
        User::auth('admin');
        $email = trim(Request::input('email'));
        if ($email === '') {
            return Base::retError('请输入邮箱');
        }
        $masked = OnlineLicense::emailSend($email);
        return Base::retSuccess('验证码已发送', ['email' => $masked]);
    }

    /**
     * 邮箱 + 验证码登录并签发在线授权
     */
    public function login()
    {
        User::auth('admin');
        $email = trim(Request::input('email'));
        $code = trim(Request::input('code'));
        if ($email === '' || $code === '') {
            return Base::retError('请输入邮箱和验证码');
        }
        $data = OnlineLicense::login($email, $code);
        return Base::retSuccess('授权成功', $data);
    }

    /**
     * 多条可用授权时，用户选定后确认签发（复用验证码）
     */
    public function login__confirm()
    {
        User::auth('admin');
        $email = trim(Request::input('email'));
        $code = trim(Request::input('code'));
        $entitlementId = (int)Request::input('entitlement_id');
        if ($email === '' || $code === '') {
            return Base::retError('请输入邮箱和验证码');
        }
        if ($entitlementId <= 0) {
            return Base::retError('请选择要使用的授权');
        }
        $data = OnlineLicense::loginConfirm($email, $code, $entitlementId);
        return Base::retSuccess('授权成功', $data);
    }

    /**
     * 邮箱 + 验证码申请试用并签发
     */
    public function trial()
    {
        User::auth('admin');
        $email = trim(Request::input('email'));
        $code = trim(Request::input('code'));
        if ($email === '' || $code === '') {
            return Base::retError('请输入邮箱和验证码');
        }
        $data = OnlineLicense::trial($email, $code);
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
