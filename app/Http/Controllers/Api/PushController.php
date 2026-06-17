<?php

namespace App\Http\Controllers\Api;

use App\Module\Base;
use App\Module\Doo;
use App\Services\DooPushClient;
use Request;

/**
 * 推送相关接口（DooPush 通道）。
 *
 * 路由：api/push/{method}（见 routes/web.php）。
 * - register：App 端登录后上报 deviceToken + userid，后端调 DooPush 设 userid 标签
 * - unregister：App 端登出/换号时调用，后端调 DooPush 解除标签
 */
class PushController extends AbstractController
{
    /**
     * 绑定推送设备到当前会员（设置 DooPush userid 标签）。
     *
     * @apiParam {String} device_token  DooPush SDK 注册返回的 deviceToken（不是平台 token）
     */
    public function register__index()
    {
        $userid = Doo::userId();
        $token = trim(Request::input('device_token', ''));
        if ($token === '') {
            return Base::retError('device_token 不能为空');
        }
        if (!DooPushClient::enabled()) {
            return Base::retError('推送服务未启用');
        }
        try {
            $resp = DooPushClient::setUserTag($token, $userid);
            if (!$resp->successful()) {
                return Base::retError('设置标签失败: ' . $resp->status());
            }
        } catch (\Throwable $e) {
            return Base::retError('设置标签异常: ' . $e->getMessage());
        }
        return Base::retSuccess('success');
    }

    /**
     * 解绑推送设备（解除 DooPush userid 标签）。
     *
     * @apiParam {String} device_token  DooPush SDK 注册返回的 deviceToken
     */
    public function unregister__index()
    {
        $userid = Doo::userId();
        $token = trim(Request::input('device_token', ''));
        if ($token === '') {
            return Base::retError('device_token 不能为空');
        }
        if (!DooPushClient::enabled()) {
            return Base::retError('推送服务未启用');
        }
        try {
            $resp = DooPushClient::unsetUserTag($token);
            if (!$resp->successful()) {
                return Base::retError('解绑标签失败: ' . $resp->status());
            }
        } catch (\Throwable $e) {
            return Base::retError('解绑标签异常: ' . $e->getMessage());
        }
        unset($userid); // 当前实现按 token 解绑；保留 userid 拉取以约束鉴权语义
        return Base::retSuccess('success');
    }
}
