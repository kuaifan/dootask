<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Timer;
use App\Module\AI;
use Carbon\Carbon;

/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $desc 参数描述、备注
 * @property array $setting
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Setting extends AbstractModel
{
    /**
     * 格式化设置参数
     * @param $value
     * @return array
     */
    public function getSettingAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $value = Base::json2array($value);
        switch ($this->name) {
            // 系统设置
            case 'system':
                $value['system_alias'] = $value['system_alias'] ?: env('APP_NAME');
                $value['image_compress'] = $value['image_compress'] ?: 'open';
                $value['image_quality'] = min(100, max(0, intval($value['image_quality']) ?: 90));
                $value['image_save_local'] = $value['image_save_local'] ?: 'open';
                $value['task_user_limit'] = min(2000, max(1, intval($value['task_user_limit']) ?: 500));
                if (!is_array($value['task_default_time']) || count($value['task_default_time']) != 2 || !Timer::isTime($value['task_default_time'][0]) || !Timer::isTime($value['task_default_time'][1])) {
                    $value['task_default_time'] = ['09:00', '18:00'];
                }
                break;

            // 文件设置
            case 'fileSetting':
                $value['permission_pack_type'] = $value['permission_pack_type'] ?: 'all';
                $value['permission_pack_userids'] = is_array($value['permission_pack_userids']) ? $value['permission_pack_userids'] : [];
                break;

            // AI 机器人设置
            case 'aibotSetting':
                if (!empty($value['claude_token']) && empty($value['claude_key'])) {
                    $value['claude_key'] = $value['claude_token'];
                }
                $array = [];
                $aiList = ['openai', 'claude', 'deepseek', 'gemini', 'grok', 'ollama', 'zhipu', 'qianwen', 'wenxin'];
                $fieldList = ['key', 'secret', 'models', 'model', 'base_url', 'agency', 'temperature', 'system'];
                foreach ($aiList as $aiName) {
                    foreach ($fieldList as $fieldName) {
                        $key = $aiName . '_' . $fieldName;
                        $content = !empty($value[$key]) ? trim($value[$key]) : '';
                        switch ($fieldName) {
                            case 'models':
                                if ($content) {
                                    $content = explode("\n", $content);
                                    $content = array_filter($content);
                                }
                                $content = is_array($content) ? implode("\n", $content) : '';
                                break;
                            case 'model':
                                $models = Setting::AIBotModels2Array($array[$key . 's'], true);
                                $content = in_array($content, $models) ? $content : ($models[0] ?? '');
                                break;
                            case 'temperature':
                                if ($content) {
                                    $content = floatval(min(1, max(0, floatval($content) ?: 0.7)));
                                }
                                break;
                        }
                        $array[$key] = $content;
                    }
                }
                $value = $array;
                break;
        }
        return $value;
    }

    /**
     * 规范任务优先级设置（确保字段完整且仅有一个默认项）
     * @param mixed $list
     * @return array<int, array{name:string,color:string,days:int,priority:int,is_default:int}>
     */
    public static function normalizeTaskPriorityList($list)
    {
        if (!is_array($list)) {
            return [];
        }
        $normalized = [];
        $defaultIndex = null;
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string)($item['name'] ?? ''));
            $color = trim((string)($item['color'] ?? ''));
            $priority = intval($item['priority'] ?? 0);
            if ($name === '' || $color === '' || $priority <= 0) {
                continue;
            }
            $days = intval($item['days'] ?? 0);
            $isDefault = !empty($item['is_default']) || !empty($item['default']);
            if ($defaultIndex === null && $isDefault) {
                $defaultIndex = count($normalized);
            }
            $normalized[] = [
                'name' => $name,
                'color' => $color,
                'days' => $days,
                'priority' => $priority,
                'is_default' => $isDefault ? 1 : 0,
            ];
        }
        if (!empty($normalized)) {
            $defaultIndex = $defaultIndex ?? 0;
            foreach ($normalized as $i => $row) {
                $normalized[$i]['is_default'] = $i === $defaultIndex ? 1 : 0;
            }
        }
        return array_values($normalized);
    }

    /**
     * 获取默认任务优先级（来自 settings.priority）
     * @param array|null $list
     * @return array|null
     */
    public static function getDefaultTaskPriorityItem($list = null)
    {
        $list = $list ?? Base::setting('priority');
        $list = self::normalizeTaskPriorityList($list);
        if (empty($list)) {
            return null;
        }
        foreach ($list as $item) {
            if (!empty($item['is_default'])) {
                return $item;
            }
        }
        return $list[0];
    }

    /**
     * 是否开启 AI 助手
     * @return bool
     */
    public static function AIOpen()
    {
        $setting = Base::setting('aibotSetting');
        if (!is_array($setting) || empty($setting)) {
            return false;
        }
        foreach (AI::TEXT_MODEL_PRIORITY as $vendor) {
            if (self::isAIBotVendorEnabled($setting, $vendor)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断 AI 机器人厂商是否启用
     * @param array $setting
     * @param string $vendor
     * @return bool
     */
    protected static function isAIBotVendorEnabled(array $setting, string $vendor): bool
    {
        $key = trim((string)($setting[$vendor . '_key'] ?? ''));
        return match ($vendor) {
            'ollama' => $key !== '' || !empty($setting['ollama_base_url']),
            'wenxin' => $key !== '' && !empty($setting['wenxin_secret']),
            default => $key !== '',
        };
    }

    /**
     * AI 机器人模型转数组
     * @param $models
     * @param bool $retValue
     * @return array
     */
    public static function AIBotModels2Array($models, $retValue = false)
    {
        $list = is_array($models) ? $models : explode("\n", $models);
        $array = [];
        foreach ($list as $item) {
            $arr = Base::newTrim(explode('|', $item . '|'));
            if ($arr[0]) {
                $array[] = [
                    'value' => $arr[0],
                    'label' => $arr[1] ?: $arr[0]
                ];
            }
        }
        if ($retValue) {
            return array_column($array, 'value');
        }
        return $array;
    }

    /**
     * 规范自定义微应用配置
     * @param array $list
     * @return array
     */
    public static function normalizeCustomMicroApps($list)
    {
        if (!is_array($list)) {
            return [];
        }
        $apps = [];
        foreach ($list as $item) {
            $app = self::normalizeCustomMicroAppItem($item);
            if ($app) {
                $apps[] = $app;
            }
        }
        return $apps;
    }

    /**
     * 根据用户身份过滤可见的自定义微应用
     * @param array $apps
     * @param \App\Models\User|null $user
     * @return array
     */
    public static function filterCustomMicroAppsForUser(array $apps, $user)
    {
        if (empty($apps)) {
            return [];
        }
        $isAdmin = $user ? $user->isAdmin() : false;
        $userId = $user ? intval($user->userid) : 0;
        $filtered = [];
        foreach ($apps as $app) {
            $visible = self::normalizeCustomMicroVisible($app['visible_to'] ?? ['admin']);
            if (!self::isCustomMicroVisibleTo($visible, $isAdmin, $userId)) {
                continue;
            }
            if (empty($app['menu_items']) || !is_array($app['menu_items'])) {
                continue;
            }
            $menus = array_values(array_filter($app['menu_items'], function ($menu) use ($isAdmin, $userId) {
                if (!isset($menu['visible_to'])) {
                    return true;
                }
                $visible = self::normalizeCustomMicroVisible($menu['visible_to']);
                return self::isCustomMicroVisibleTo($visible, $isAdmin, $userId);
            }));
            if (empty($menus)) {
                continue;
            }
            $app['menu_items'] = $menus;
            $filtered[] = $app;
        }
        return $filtered;
    }

    /**
     * 将存储结构转换成 appstore 接口同款格式
     * @param array $apps
     * @return array
     */
    public static function formatCustomMicroAppsForResponse(array $apps)
    {
        return array_values(array_map(function ($app) {
            unset($app['visible_to']);
            if (!empty($app['menu_items']) && is_array($app['menu_items'])) {
                $app['menu_items'] = array_values(array_map(function ($menu) {
                    $menu['keep_alive'] = isset($menu['keep_alive']) ? (bool)$menu['keep_alive'] : true;
                    $menu['disable_scope_css'] = (bool)($menu['disable_scope_css'] ?? false);
                    $menu['auto_dark_theme'] = isset($menu['auto_dark_theme']) ? (bool)$menu['auto_dark_theme'] : true;
                    $menu['transparent'] = (bool)($menu['transparent'] ?? false);
                    if (isset($menu['visible_to'])) {
                        unset($menu['visible_to']);
                    }
                    return $menu;
                }, $app['menu_items']));
            }
            return $app;
        }, $apps));
    }

    /**
     * 规范自定义微应用
     * @param array $item
     * @return array|null
     */
    protected static function normalizeCustomMicroAppItem($item)
    {
        if (!is_array($item)) {
            return null;
        }
        $id = trim($item['id'] ?? '');
        if ($id === '') {
            return null;
        }
        $name = Base::newTrim($item['name'] ?? '');
        $version = Base::newTrim($item['version'] ?? '') ?: 'custom';
        $menuItems = [];
        if (isset($item['menu_items']) && is_array($item['menu_items'])) {
            $menuItems = $item['menu_items'];
        } elseif (isset($item['menu']) && is_array($item['menu'])) {
            $menuItems = [$item['menu']];
        }
        if (empty($menuItems)) {
            return null;
        }
        $normalizedMenus = [];
        foreach ($menuItems as $menu) {
            $formattedMenu = self::normalizeCustomMicroMenuItem($menu, $name ?: $id);
            if ($formattedMenu) {
                $normalizedMenus[] = $formattedMenu;
            }
        }
        if (empty($normalizedMenus)) {
            return null;
        }
        return Base::newTrim([
            'id' => $id,
            'name' => $name,
            'version' => $version,
            'menu_items' => $normalizedMenus,
            'visible_to' => self::normalizeCustomMicroVisible($item['visible_to'] ?? 'admin'),
        ]);
    }

    /**
     * 规范自定义微应用菜单项
     * @param array $menu
     * @param string $fallbackLabel
     * @return array|null
     */
    protected static function normalizeCustomMicroMenuItem($menu, $fallbackLabel = '')
    {
        if (!is_array($menu)) {
            return null;
        }
        $url = trim($menu['url'] ?? '');
        if ($url === '') {
            return null;
        }
        $location = trim($menu['location'] ?? 'application');
        $label = trim($menu['label'] ?? $fallbackLabel);
        $type = strtolower(trim($menu['type'] ?? 'iframe'));
        $payload = [
            'location' => $location,
            'label' => $label,
            'icon' => Base::newTrim($menu['icon'] ?? ''),
            'url' => $url,
            'type' => $type,
            'keep_alive' => isset($menu['keep_alive']) ? (bool)$menu['keep_alive'] : true,
            'disable_scope_css' => (bool)($menu['disable_scope_css'] ?? false),
            'auto_dark_theme' => isset($menu['auto_dark_theme']) ? (bool)$menu['auto_dark_theme'] : true,
            'transparent' => (bool)($menu['transparent'] ?? false),
        ];
        if (!empty($menu['background'])) {
            $payload['background'] = Base::newTrim($menu['background']);
        }
        if (!empty($menu['capsule']) && is_array($menu['capsule'])) {
            $payload['capsule'] = Base::newTrim($menu['capsule']);
        }
        return $payload;
    }

    /**
     * 规范自定义微应用可见范围
     * @param mixed $value
     * @return array
     */
    protected static function normalizeCustomMicroVisible($value)
    {
        if (is_array($value)) {
            $list = array_filter(array_map('trim', $value));
        } else {
            $list = array_filter(array_map('trim', explode(',', (string)$value)));
        }
        if (empty($list)) {
            return ['admin'];
        }
        if (in_array('all', $list)) {
            return ['all'];
        }
        return array_values($list);
    }

    /**
     * 判断自定义微应用是否可见
     * @param array $visible
     * @param bool $isAdmin
     * @param int $userId
     * @return bool
     */
    protected static function isCustomMicroVisibleTo(array $visible, bool $isAdmin, int $userId)
    {
        if (in_array('all', $visible)) {
            return true;
        }
        if ($isAdmin && in_array('admin', $visible)) {
            return true;
        }
        if ($userId > 0 && in_array((string)$userId, $visible, true)) {
            return true;
        }
        return false;
    }

    /**
     * 验证邮箱地址（过滤忽略地址）
     * @param $array
     * @param \Closure $resultClosure
     * @param \Closure|null $emptyClosure
     * @return array|mixed
     */
    public static function validateAddr($array, $resultClosure, $emptyClosure = null)
    {
        if (!is_array($array)) {
            $array = [$array];
        }
        $ignoreAddr = Base::settingFind('emailSetting', 'ignore_addr');
        $ignoreAddr = explode("\n", $ignoreAddr);
        $ignoreArray = ['admin@dootask.com', 'test@dootask.com'];
        foreach ($ignoreAddr as $item) {
            if (Base::isEmail($item)) {
                $ignoreArray[] = trim($item);
            }
        }
        if ($ignoreArray) {
            $array = array_diff($array, $ignoreArray);
        }
        if ($array) {
            if ($resultClosure instanceof \Closure) {
                foreach ($array as $value) {
                    $resultClosure($value);
                }
            }
        } else {
            if ($emptyClosure instanceof \Closure) {
                $emptyClosure();
            }
        }
        return $array;
    }

    /**
     * 验证消息限制
     * @param $type
     * @param $msg
     * @return void
     */
    public static function validateMsgLimit($type, $msg)
    {
        $keyName = 'msg_edit_limit';
        $error = '此消息不可修改';
        if ($type == 'rev') {
            $keyName = 'msg_rev_limit';
            $error = '此消息不可撤回';
        }
        $limitNum = intval(Base::settingFind('system', $keyName, 0));
        if ($limitNum <= 0) {
            return;
        }
        if ($msg instanceof WebSocketDialogMsg) {
            $dialogMsg = $msg;
        } else {
            $dialogMsg = WebSocketDialogMsg::find($msg);
        }
        if (!$dialogMsg) {
            return;
        }
        $limitTime = Carbon::parse($dialogMsg->created_at)->addMinutes($limitNum);
        if ($limitTime->lt(Carbon::now())) {
            throw new ApiException('已超过' . Doo::translate(Base::forumMinuteDay($limitNum)) . '，' . $error);
        }
    }
}
