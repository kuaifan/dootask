<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Services\RequestContext;
use Symfony\Component\Yaml\Yaml;

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
                'ai' => 'AI Robot',
                'face' => 'Face check-in',
                'appstore' => 'AppStore',
                'approve' => 'Approval',
                'office' => 'OnlyOffice',
                'drawio' => 'Drawio',
                'minder' => 'Minder',
                'search' => 'ZincSearch',
                default => $appId,
            };
            throw new ApiException("应用「{$name}」未安装", [], 0, false);
        }
    }
}
