<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\UserDepartment;
use App\Services\RequestContext;
use Symfony\Component\Yaml\Yaml;
use App\Module\Base;
use App\Module\Ihttp;

class Apps
{
    /**
     * 判断应用是否已安装
     *
     * @param string $appId 应用ID（名称）
     * @return bool 如果应用已安装返回 true，否则返回 false
     */
    public static function isInstalled(string $appId): bool
    {
        if ($appId === 'appstore') {
            return true;
        }

        $key = 'app_installed_' . $appId;
        if (RequestContext::has($key)) {
            return (bool) RequestContext::get($key, false);
        }

        $configFile = base_path('docker/appstore/config/' . $appId . '/config.yml');
        $installed = false;
        if (file_exists($configFile)) {
            $configData = Yaml::parseFile($configFile);
            $installed = $configData['status'] === 'installed';
        }

        return RequestContext::save($key, $installed);
    }

    /**
     * 判断应用是否已安装，如果未安装则抛出异常
     * @param string $appId
     * @return void
     */
    public static function isInstalledThrow(string $appId): void
    {
        if (!self::isInstalled($appId)) {
            $name = match ($appId) {
                'ai' => 'AI Assistant',
                'face' => 'Face check-in',
                'appstore' => 'AppStore',
                'approve' => 'Approval',
                'office' => 'OnlyOffice',
                'drawio' => 'Drawio',
                'minder' => 'Minder',
                'manticore' => 'Manticore Search',
                default => $appId,
            };
            throw new ApiException("应用「{$name}」未安装", [], 0, false);
        }
    }

    /**
     * Dispatch user lifecycle hook to appstore (user_onboard/user_offboard/user_update).
     *
     * @param User $user 用户对象
     * @param string $action Hook 动作: user_onboard, user_offboard, user_update
     * @param string $eventType 事件类型: onboard, restore, offboarded, delete, profile_update, admin_update
     * @param array $changedFields 变更字段列表（仅 user_update 时有值）
     */
    public static function dispatchUserHook(User $user, string $action, string $eventType = '', array $changedFields = []): void
    {
        $appKey = env('APP_KEY', '');
        if (empty($appKey)) {
            info('[appstore_hook] APP_KEY is empty, skip dispatchUserHook');
            return;
        }

        // 获取用户部门信息
        $departments = [];
        if (!empty($user->department)) {
            $deptIds = is_array($user->department)
                ? $user->department
                : array_filter(explode(',', $user->department));
            if (!empty($deptIds)) {
                $deptList = UserDepartment::whereIn('id', $deptIds)->get(['id', 'name']);
                foreach ($deptList as $dept) {
                    $departments[] = [
                        'id' => (string) $dept->id,
                        'name' => (string) $dept->name,
                    ];
                }
            }
        }

        $url = sprintf('http://appstore/api/v1/internal/hooks/%s', $action);
        $payload = [
            'user' => [
                'id' => (string) $user->userid,
                'email' => (string) $user->email,
                'name' => (string) $user->nickname,
                'role' => $user->isAdmin() ? 'admin' : 'normal',
                'tel' => (string) ($user->tel ?? ''),
                'profession' => (string) ($user->profession ?? ''),
                'birthday' => $user->birthday ? (string) $user->birthday : '',
                'address' => (string) ($user->address ?? ''),
                'introduction' => (string) ($user->introduction ?? ''),
                'departments' => $departments,
            ],
            'event_type' => $eventType,
            'changed_fields' => $changedFields,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . md5($appKey),
            'Version' => Base::getVersion(),
        ];

        $resp = Ihttp::ihttp_request($url, json_encode($payload, JSON_UNESCAPED_UNICODE), $headers, 5);
        if (Base::isError($resp)) {
            info('[appstore_hook] dispatch fail', [
                'url' => $url,
                'payload' => $payload,
                'error' => $resp,
            ]);
        }
    }
}
