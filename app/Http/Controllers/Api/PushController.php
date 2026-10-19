<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\InvokeController;
use App\Models\User;
use App\Module\AppPush\DeviceRegistry;
use App\Module\Base;
use Request;

/**
 * @apiDefine push APP 推送
 */
class PushController extends InvokeController
{
    /**
     * @api {post} api/push/device 更新或移除 DooPush 设备
     *
     * @apiDescription 同时支持 GET 和 POST。update 需要有效 token 和 ios/android 平台；remove 仅按请求头 token 的 MD5 匹配解绑，允许 token 已失效。
     * @apiVersion 1.0.0
     * @apiGroup push
     * @apiName device
     *
     * @apiParam {String="update","remove"} [action=update] 操作类型
     * @apiParam {String} device_id DooPush 设备ID，1至64位纯数字字符串
     * @apiParam {String="apns","fcm","hms","honor","xiaomi","oppo","vivo","meizu"} [vendor] update 时必填的厂商通道
     * @apiParam {String} [unique_id] 壳的唯一标识，至少5个字符时清理同后缀旧友盟别名
     * @apiParam {String} [device_model] 设备型号，最多255个字符
     * @apiParam {String} [app_version] 应用版本代码
     * @apiParam {String} [app_version_name] 应用版本名称，与代码组合后最多100个字符
     * @apiParam {String} [user_agent] 浏览器信息
     * @apiParam {String} [is_notified] true、字符串true、1、字符串1表示有通知权限
     *
     * @apiSuccess {Number} ret 返回状态码（1正确、0错误）
     * @apiSuccess {String} msg 返回信息（错误描述）
     * @apiSuccess {Array} data 返回数据（空数组）
     */
    public function device()
    {
        $data = Request::input();
        $action = $data['action'] ?? 'update';
        $deviceId = $data['device_id'] ?? '';
        if (!is_string($deviceId) || !preg_match('/^[0-9]{1,64}$/D', $deviceId)) {
            return Base::retError('推送设备ID必须为1至64位数字');
        }
        if (!in_array($action, ['update', 'remove'], true)) {
            return Base::retError('参数错误');
        }
        if ($action === 'remove') {
            DeviceRegistry::remove($deviceId, (string)Request::header('token', ''));
            return Base::retSuccess('删除成功');
        }

        $user = User::auth();
        $platform = Base::platform();
        if (!in_array($platform, ['ios', 'android'], true)) {
            return Base::retError('设备类型错误');
        }
        if (!in_array($data['vendor'] ?? '', ['apns', 'fcm', 'hms', 'honor', 'xiaomi', 'oppo', 'vivo', 'meizu'], true)) {
            return Base::retError('推送厂商错误');
        }
        foreach (['unique_id' => 255, 'device_model' => 255, 'app_version' => 100, 'app_version_name' => 100, 'user_agent' => 65535] as $field => $limit) {
            if (isset($data[$field]) && (!is_string($data[$field]) || mb_strlen($data[$field]) > $limit)) {
                return Base::retError('参数错误');
            }
        }
        $version = empty($data['app_version']) ? '' : (($data['app_version_name'] ?? '') . " ({$data['app_version']})");
        if (mb_strlen($version) > 100 || strlen($data['user_agent'] ?? '') > 65535) {
            return Base::retError('参数错误');
        }
        DeviceRegistry::update($user->userid, [
            'device_id' => $deviceId,
            'vendor' => $data['vendor'],
            'platform' => $platform,
            'device' => $data['device_model'] ?? '',
            'version' => $version,
            'ua' => $data['user_agent'] ?? '',
            'is_notified' => in_array($data['is_notified'] ?? false, [true, 'true', 1, '1'], true) ? 1 : 0,
        ], $data['unique_id'] ?? '');
        return Base::retSuccess('保存成功');
    }
}
