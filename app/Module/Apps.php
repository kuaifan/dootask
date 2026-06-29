<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\Setting;
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

        return RequestContext::save($key, self::loadInstalledConfig($appId) !== null);
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
     * appstore 目录下的绝对路径（统一 docker/appstore 前缀）。
     *
     * @param string $relative 相对 docker/appstore 的路径
     * @return string
     */
    private static function appstorePath(string $relative): string
    {
        return base_path('docker/appstore/' . ltrim($relative, '/'));
    }

    /**
     * 读取并校验某应用的 appstore config.yml；仅当文件存在且 status=installed 时返回解析后的配置数组。
     *
     * @param string $appId
     * @return array|null
     */
    private static function loadInstalledConfig(string $appId): ?array
    {
        $appId = trim($appId);
        if ($appId === '' || $appId === 'appstore') {
            return null;
        }
        $configFile = self::appstorePath("config/{$appId}/config.yml");
        if (!file_exists($configFile)) {
            return null;
        }
        $config = Yaml::parseFile($configFile);
        if (!is_array($config) || ($config['status'] ?? '') !== 'installed') {
            return null;
        }
        return $config;
    }

    /**
     * 将单个 menu_items 项映射为角标用的菜单配置。
     *
     * @param array $menu
     * @param mixed $visibleDefault 应用级默认可见范围
     * @return array ['key'=>string,'visible'=>array,'badge_clear_on_open'=>bool]
     */
    private static function mapMenuItem(array $menu, $visibleDefault): array
    {
        return [
            'key' => trim((string)($menu['key'] ?? '')),
            'visible' => Setting::normalizeCustomMicroVisible($menu['visible_to'] ?? $visibleDefault),
            'badge_clear_on_open' => (bool)($menu['badge_clear_on_open'] ?? false),
        ];
    }

    /**
     * 获取（必要时生成并持久化）应用的独立密钥 APP_SECRET。
     *
     * 与全局 APP_KEY 不同，APP_SECRET 每个已安装应用独立、唯一，持久化在应用自身的
     * docker/appstore/config/{appid}/config.yml（与 KB_INGEST_TOKEN 等每应用参数同源），
     * 由 appstore 安装链路按内置 compose 变量 APP_SECRET 注入插件容器。
     * 此处主程序侧负责生成/持久化与校验；首次需要时若不存在则惰性生成，保证主程序可独立验证。
     *
     * @param string $appId 应用ID
     * @return string 应用密钥；应用未安装或非插件应用时返回空字符串
     */
    public static function appSecret(string $appId): string
    {
        $appId = trim($appId);
        $config = self::loadInstalledConfig($appId);
        if ($config === null) {
            return '';
        }
        $secret = trim((string)($config['app_secret'] ?? ''));
        if ($secret !== '') {
            return $secret;
        }
        // 首次需要时生成并持久化（按 appid 唯一）
        $secret = Base::generatePassword(48);
        $config['app_secret'] = $secret;
        try {
            file_put_contents(self::appstorePath("config/{$appId}/config.yml"), Yaml::dump($config, 4, 2));
        } catch (\Throwable $e) {
            info('[app_badge] persist app_secret fail', ['appid' => $appId, 'error' => $e->getMessage()]);
            return '';
        }
        return $secret;
    }

    /**
     * 解析应用的菜单角标配置（菜单 key 列表与各自的可见范围）。
     *
     * 同时覆盖两类应用：
     *  - 插件应用：读取 docker/appstore/apps/{appid}/{version}/config.yml 的 menu_items
     *  - 自定义微应用：读取 microapp_menu 设置
     *
     * @param string $appId 应用ID
     * @return array|null ['source'=>'plugin'|'custom', 'menus'=>[['key'=>string,'visible'=>array,'badge_clear_on_open'=>bool], ...]]；应用不存在返回 null
     */
    public static function appMenuConfig(string $appId): ?array
    {
        $appId = trim($appId);
        if ($appId === '') {
            return null;
        }
        // 插件应用
        $config = self::loadInstalledConfig($appId);
        if ($config !== null) {
            $version = trim((string)($config['install_version'] ?? ''));
            return [
                'source' => 'plugin',
                'menus' => self::readPluginMenus($appId, $version),
            ];
        }
        // 自定义微应用
        $apps = Base::setting('microapp_menu');
        if (is_array($apps)) {
            foreach ($apps as $app) {
                if (!is_array($app) || trim((string)($app['id'] ?? '')) !== $appId) {
                    continue;
                }
                $appVisibleDefault = $app['visible_to'] ?? 'admin';
                $menus = [];
                foreach (($app['menu_items'] ?? []) as $menu) {
                    if (!is_array($menu)) {
                        continue;
                    }
                    $menus[] = self::mapMenuItem($menu, $appVisibleDefault);
                }
                return ['source' => 'custom', 'menus' => $menus];
            }
        }
        return null;
    }

    /**
     * 读取插件包 config.yml 的菜单配置。
     *
     * @param string $appId
     * @param string $version 已安装版本
     * @return array
     */
    private static function readPluginMenus(string $appId, string $version): array
    {
        $paths = [];
        if ($version !== '') {
            $paths[] = self::appstorePath("apps/{$appId}/{$version}/config.yml");
        }
        $paths[] = self::appstorePath("apps/{$appId}/config.yml");
        $pkg = null;
        foreach ($paths as $p) {
            if (file_exists($p) && is_readable($p)) {
                try {
                    $pkg = Yaml::parseFile($p);
                } catch (\Throwable $e) {
                    $pkg = null;
                }
                if (is_array($pkg)) {
                    break;
                }
            }
        }
        $menus = [];
        if (is_array($pkg) && !empty($pkg['menu_items']) && is_array($pkg['menu_items'])) {
            $appVisibleDefault = $pkg['visible_to'] ?? 'all';
            foreach ($pkg['menu_items'] as $menu) {
                if (!is_array($menu)) {
                    continue;
                }
                $menus[] = self::mapMenuItem($menu, $appVisibleDefault);
            }
        }
        if (empty($menus)) {
            // 读不到包配置（权限/缺失）时退化为单一默认菜单，仍可对第一个菜单设角标
            $menus[] = ['key' => '', 'visible' => ['all'], 'badge_clear_on_open' => false];
        }
        return $menus;
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
        $appKey = config('app.key') ?: '';
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
