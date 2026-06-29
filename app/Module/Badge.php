<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\AppBadge;
use App\Models\Setting;
use App\Models\User;
use App\Tasks\PushTask;

/**
 * 插件 / 微应用菜单角标业务编排。
 *
 * 角标真值归插件（应用密钥写入），主程序仅作为存储与分发：
 *  - 绝对设置/清除 app_badges 行（仅存非清除态）
 *  - 通过 WebSocket（PushTask）向在线用户实时推送 appBadge 消息
 *  - 提供初始同步所需的用户角标快照
 */
class Badge
{
    /**
     * 设置角标（应用密钥鉴权场景）：校验密钥/菜单/可见性，绝对设置并实时推送。
     *
     * @param string $appId
     * @param string $secret 请求携带的应用密钥
     * @param mixed $userid 目标用户ID（单个或数组）
     * @param string $menuKeyInput 请求中的 menu_key（可空，留空取第一个菜单）
     * @param mixed $count 角标数字
     * @param mixed $dot 是否红点
     * @return array 响应数据
     * @throws ApiException 参数/密钥/应用/菜单校验失败
     */
    public static function set(string $appId, string $secret, $userid, string $menuKeyInput, $count, $dot): array
    {
        if ($appId === '') {
            throw new ApiException('参数错误');
        }
        if ($secret === '') {
            throw new ApiException('密钥无效');
        }
        $expect = Apps::appSecret($appId);
        if ($expect === '' || !hash_equals($expect, $secret)) {
            throw new ApiException('密钥无效');
        }
        $menu = self::resolveAppMenu($appId, $menuKeyInput);
        $menuKey = (string)($menu['key'] ?? '');
        $userids = self::normalizeUserids($userid);
        if (empty($userids)) {
            throw new ApiException('参数错误');
        }
        // 仅保留对该应用菜单有可见权限的用户
        $userids = self::filterVisibleUserids($menu, $userids);
        if (empty($userids)) {
            return ['appid' => $appId, 'menu_key' => $menuKey, 'affected' => 0];
        }
        $count = max(0, intval($count));
        $dot = filter_var($dot, FILTER_VALIDATE_BOOLEAN);
        self::applySet($appId, $menuKey, $userids, $count, $dot);
        self::push($appId, $menuKey, $userids, $count, $dot);
        return [
            'appid' => $appId,
            'menu_key' => $menuKey,
            'count' => $count,
            'dot' => $dot,
            'affected' => count($userids),
        ];
    }

    /**
     * 清除指定用户在某应用某菜单的角标（用户 token 鉴权场景），并推送多端一致。
     *
     * @param int $userid 当前用户ID
     * @param string $appId
     * @param string $menuKeyInput 请求中的 menu_key（可空）
     * @return array 响应数据
     * @throws ApiException 参数/应用/菜单校验失败
     */
    public static function clearForUser(int $userid, string $appId, string $menuKeyInput): array
    {
        if ($userid <= 0) {
            throw new ApiException('参数错误');
        }
        if ($appId === '') {
            throw new ApiException('参数错误');
        }
        $menu = self::resolveAppMenu($appId, $menuKeyInput);
        $menuKey = (string)($menu['key'] ?? '');
        AppBadge::whereAppId($appId)->whereMenuKey($menuKey)->whereUserid($userid)->delete();
        // 推送给该用户的所有在线端，保证多端一致
        self::push($appId, $menuKey, [$userid], 0, false);
        return [
            'appid' => $appId,
            'menu_key' => $menuKey,
        ];
    }

    /**
     * 解析应用菜单配置并定位目标菜单。
     *
     * @param string $appId
     * @param string $menuKeyInput
     * @return array 命中的菜单配置
     * @throws ApiException 应用未安装或菜单不存在
     */
    private static function resolveAppMenu(string $appId, string $menuKeyInput): array
    {
        $config = Apps::appMenuConfig($appId);
        if ($config === null) {
            throw new ApiException('应用未安装');
        }
        $menu = self::resolveMenu($config['menus'], $menuKeyInput);
        if ($menu === null) {
            throw new ApiException('菜单不存在');
        }
        return $menu;
    }

    /**
     * 归一化目标用户ID：单值/数组 -> 去重去零的整型数组。
     *
     * @param mixed $userid
     * @return int[]
     */
    private static function normalizeUserids($userid): array
    {
        if (is_string($userid) || is_numeric($userid)) {
            $userid = [$userid];
        }
        if (!is_array($userid)) {
            return [];
        }
        return self::intIds($userid);
    }

    /**
     * 数组 -> 去重去零的整型数组。
     *
     * @param array $ids
     * @return int[]
     */
    private static function intIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
    }

    /**
     * 解析目标菜单：menu_key 为空时取第一个菜单；否则必须命中已声明的菜单 key。
     *
     * @param array $menus appMenuConfig 返回的 menus
     * @param string $menuKey 请求中的 menu_key（可空）
     * @return array|null 命中的菜单配置；非法 menu_key 返回 null
     */
    private static function resolveMenu(array $menus, string $menuKey): ?array
    {
        if ($menuKey === '') {
            return $menus[0] ?? ['key' => '', 'visible' => ['all'], 'badge_clear_on_open' => false];
        }
        foreach ($menus as $menu) {
            if (($menu['key'] ?? '') === $menuKey) {
                return $menu;
            }
        }
        return null;
    }

    /**
     * 按菜单可见范围过滤目标用户，仅保留对该应用菜单有权限的用户。
     *
     * @param array $menu 命中的菜单配置（含 visible）
     * @param int[] $userids
     * @return int[] 允许的用户ID
     */
    private static function filterVisibleUserids(array $menu, array $userids): array
    {
        if (empty($userids)) {
            return [];
        }
        $visible = $menu['visible'] ?? ['all'];
        if (in_array('all', $visible)) {
            return $userids;
        }
        $allowed = [];
        $users = User::whereIn('userid', $userids)->get(['userid', 'identity']);
        foreach ($users as $user) {
            if (Setting::isCustomMicroVisibleTo($visible, $user->isAdmin(), (int)$user->userid)) {
                $allowed[] = (int)$user->userid;
            }
        }
        return $allowed;
    }

    /**
     * 绝对设置角标（幂等）。count=0 且 dot=false 即清除（删行）。
     *
     * @param string $appId
     * @param string $menuKey
     * @param int[] $userids
     * @param int $count
     * @param bool $dot
     * @return void
     */
    private static function applySet(string $appId, string $menuKey, array $userids, int $count, bool $dot): void
    {
        if (empty($userids)) {
            return;
        }
        // 清除态：一条 whereIn 删除
        if ($count === 0 && !$dot) {
            AppBadge::whereAppId($appId)->whereMenuKey($menuKey)->whereIn('userid', $userids)->delete();
            return;
        }
        // 非清除态：依赖唯一键 (app_id,menu_key,userid) 批量 upsert
        $now = date('Y-m-d H:i:s');
        $rows = array_map(fn($uid) => [
            'app_id' => $appId,
            'menu_key' => $menuKey,
            'userid' => (int)$uid,
            'count' => $count,
            'dot' => $dot,
            'updated_at' => $now,
        ], $userids);
        AppBadge::upsert($rows, ['app_id', 'menu_key', 'userid'], ['count', 'dot', 'updated_at']);
    }

    /**
     * 清除某应用的全部角标（应用卸载时）。
     *
     * @param string $appId
     * @return void
     */
    public static function clearByApp(string $appId): void
    {
        $appId = trim($appId);
        if ($appId === '') {
            return;
        }
        AppBadge::whereAppId($appId)->delete();
    }

    /**
     * 清除某用户的全部角标（用户离职时）。
     *
     * @param int $userid
     * @return void
     */
    public static function clearByUser(int $userid): void
    {
        if ($userid <= 0) {
            return;
        }
        AppBadge::whereUserid($userid)->delete();
    }

    /**
     * 获取用户当前全部角标快照，用于前端初始同步。
     * 过滤掉应用已不存在（卸载 / 自定义微应用被删除）的行，避免父级聚合统计残留数据。
     *
     * @param int $userid
     * @return array app_id => menu_key => ['count'=>int,'dot'=>bool]
     */
    public static function userBadges(int $userid): array
    {
        $map = [];
        if ($userid <= 0) {
            return $map;
        }
        $rows = AppBadge::whereUserid($userid)->get(['app_id', 'menu_key', 'count', 'dot']);
        if ($rows->isEmpty()) {
            return $map;
        }
        // 自定义微应用 id 集合一次性收，避免每行重复 foreach
        $customIds = [];
        $customApps = Base::setting('microapp_menu');
        if (is_array($customApps)) {
            foreach ($customApps as $app) {
                if (is_array($app) && !empty($app['id'])) {
                    $customIds[(string)$app['id']] = true;
                }
            }
        }
        // 按 app_id 缓存判定结果：插件应用走 Apps::isInstalled（单 yaml + 请求级缓存），
        // 自定义微应用查 set，避免每行都读 yaml / 遍历 setting。
        $exists = [];
        foreach ($rows as $row) {
            $appId = (string)$row->app_id;
            if (!isset($exists[$appId])) {
                $exists[$appId] = isset($customIds[$appId]) || Apps::isInstalled($appId);
            }
            if (!$exists[$appId]) {
                continue;
            }
            $map[$appId][$row->menu_key] = [
                'count' => (int)$row->count,
                'dot' => (bool)$row->dot,
            ];
        }
        return $map;
    }

    /**
     * 向在线用户实时推送角标变更（仅投递，不补发离线）。
     *
     * @param string $appId
     * @param string $menuKey
     * @param int[] $userids
     * @param int $count
     * @param bool $dot
     * @return void
     */
    public static function push(string $appId, string $menuKey, array $userids, int $count, bool $dot): void
    {
        $userids = self::intIds($userids);
        if (empty($userids)) {
            return;
        }
        PushTask::push([
            'userid' => $userids,
            'msg' => [
                'type' => 'appBadge',
                'data' => [
                    'appid' => $appId,
                    'menu_key' => $menuKey,
                    'count' => $count,
                    'dot' => $dot,
                ],
            ],
        ], false);
    }
}
