<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\User;
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
            return RequestContext::get($key);
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
                'search' => 'ZincSearch',
                'seekdb' => 'SeekDB',
                default => $appId,
            };
            throw new ApiException("应用「{$name}」未安装", [], 0, false);
        }
    }

    /**
     * Dispatch user lifecycle hook to appstore (onboard/offboard/delete/restore).
     */
    public static function dispatchUserHook(User $user, string $action, string $eventType = ''): void
    {
        $appKey = env('APP_KEY', '');
        if (empty($appKey)) {
            info('[appstore_hook] APP_KEY is empty, skip dispatchUserHook');
            return;
        }

        $url = sprintf('http://appstore/api/v1/internal/hooks/%s', $action);
        $payload = [
            'user' => [
                'id' => (string) $user->userid,
                'email' => (string) $user->email,
                'name' => (string) $user->nickname,
                'role' => in_array('admin', $user->identity ?? []) ? 'admin' : 'normal',
            ],
        ];
        if ($eventType !== '') {
            $payload['event_type'] = $eventType;
        }

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
