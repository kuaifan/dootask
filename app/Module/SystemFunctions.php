<?php

namespace App\Module;

use App\Services\RequestContext;
use Symfony\Component\Yaml\Yaml;

/**
 * 「系统功能」内置应用（appstore 应用 system-functions）。
 *
 * 该应用不启动容器，只通过应用字段保存各内置功能的开关；
 * 主程序据此统一控制工作报告、日历、我的收藏、最近打开、签到打卡的入口。
 * 仅控制入口显示，不拦截接口、不影响数据。
 */
class SystemFunctions
{
    const APP_ID = 'system-functions';

    /**
     * 功能标识 => 应用字段名
     */
    const FUNCTIONS = [
        'report' => 'REPORT',
        'calendar' => 'CALENDAR',
        'favorite' => 'FAVORITE',
        'recent' => 'RECENT',
        'checkin' => 'CHECKIN',
    ];

    /**
     * 应用安装配置文件路径
     *
     * @return string
     */
    public static function configFile(): string
    {
        return base_path('docker/appstore/config/' . self::APP_ID . '/config.yml');
    }

    /**
     * 获取应用安装状态与各功能的启用状态。
     * 应用未安装时全部为停用；已安装时缺失的字段视为启用（字段默认值即启用）。
     *
     * @return array ['installed' => bool, 'functions' => ['report' => bool, ...]]
     */
    public static function status(): array
    {
        $key = 'system_functions_status';
        if (RequestContext::has($key)) {
            return RequestContext::get($key);
        }
        $params = Apps::isInstalled(self::APP_ID) ? self::readParams() : null;
        $functions = [];
        foreach (self::FUNCTIONS as $name => $field) {
            $functions[$name] = $params !== null && self::isOn($params[$field] ?? true);
        }
        return RequestContext::save($key, [
            'installed' => $params !== null,
            'functions' => $functions,
        ]);
    }

    /**
     * 指定功能是否启用
     *
     * @param string $name 功能标识（report/calendar/favorite/recent/checkin）
     * @return bool
     */
    public static function enabled(string $name): bool
    {
        return (bool)(self::status()['functions'][$name] ?? false);
    }

    /**
     * 读取应用安装参数
     *
     * @return array
     */
    private static function readParams(): array
    {
        try {
            $config = Yaml::parseFile(self::configFile());
        } catch (\Throwable $e) {
            return [];
        }
        $params = is_array($config) ? ($config['params'] ?? []) : [];
        return is_array($params) ? $params : [];
    }

    /**
     * 开关值是否为开启（兼容布尔值与字符串）
     *
     * @param mixed $value
     * @return bool
     */
    private static function isOn($value): bool
    {
        if (is_string($value)) {
            return !in_array(strtolower(trim($value)), ['', '0', 'false', 'off', 'no'], true);
        }
        return (bool)$value;
    }
}
