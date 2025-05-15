<?php

namespace App\Module;

use Cache;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Apps
{
    // 受保护的服务名称列表
    protected static array $protectedServiceNames = [
        'php',
        'nginx',
        'redis',
        'mariadb',
        'office',
        'fileview',
        'drawio-webapp',
        'drawio-expont',
        'minder',
        'approve',
        'ai',
        'face',
        'search',
        'appstore',
    ];

    // 软件源列表URL
    protected static string $sourcesUrl = 'https://appstore.dootask.com/sources.list';

    /**
     * 获取应用列表
     *
     * @return array
     */
    public static function appList(): array
    {
        $apps = [];
        $baseDir = base_path('docker/appstore/apps');
        $dirs = scandir($baseDir);
        foreach ($dirs as $dir) {
            // 跳过当前目录、父目录和隐藏文件
            if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
                continue;
            }
            $apps[] = [
                'name' => $dir,
                'info' => self::getAppInfo($dir),
                'config' => self::getAppConfig($dir),
                'versions' => self::getAvailableVersions($dir),
            ];
        }
        return Base::retSuccess("success", $apps);
    }

    /**
     * 获取应用信息（比列表多返回了 document）
     *
     * @param string $appName 应用名称
     * @return array
     */
    public static function appInfo(string $appName): array
    {
        return Base::retSuccess("success", [
            'name' => $appName,
            'info' => self::getAppInfo($appName),
            'config' => self::getAppConfig($appName),
            'versions' => self::getAvailableVersions($appName),
            'document' => self::getAppDocument($appName),
        ]);
    }

    /**
     * 执行docker-compose up命令
     *
     * @param string $appName
     * @param string $version
     * @param string $command
     * @return array
     */
    public static function dockerComposeUp(string $appName, string $version = 'latest', string $command = 'up'): array
    {
        // 结果集合
        $RESULTS = [];

        // 获取版本信息
        $versions = self::getAvailableVersions($appName);
        if (empty($versions)) {
            return Base::retError("没有可用的版本");
        }
        if (strtolower($version) !== 'latest') {
            $versions = array_filter($versions, function ($item) use ($version) {
                return $item['version'] === ltrim($version, 'v');
            });
        }
        $versionInfo = array_shift($versions);
        if (empty($versionInfo)) {
            return Base::retError("没有找到版本 {$version}");
        }

        // 获取安装配置信息
        $appConfig = self::getAppConfig($appName);

        // 检查是否需要卸载旧版本
        if ($command === 'up' && $appConfig['status'] === 'installed' && $appConfig['require_uninstalls']) {
            foreach ($appConfig['require_uninstalls'] as $requireUninstall) {
                if (version_compare($versionInfo['version'], $requireUninstall['version'], $requireUninstall['operator'])) {
                    $reason = !empty($requireUninstall['reason']) ? "（原因：{$requireUninstall['reason']}）" : '';
                    return Base::retError("更新版本 {$versionInfo['version']}，需要先卸载已安装的版本{$reason}");
                }
            }
        }

        // 生成docker-compose.yml文件
        $composeFile = base_path('docker/appstore/apps/' . $appName . '/' . $versionInfo['version'] . '/docker-compose.yml');
        $res = self::generateDockerComposeYml($composeFile, $appConfig['params'], $appConfig['resources']);
        if (Base::isError($res)) {
            return $res;
        }
        $RESULTS['generate'] = $res['data'];

        // 生成nginx文件
        $nginxSourceFile = base_path('docker/appstore/apps/' . $appName . '/' . $versionInfo['version'] . '/nginx.conf');
        $nginxTargetFile = base_path('docker/appstore/configs/' . $appName . '/nginx.conf');
        $nginxContent = '';
        if ($command === 'up' && file_exists($nginxSourceFile)) {
            $nginxContent = file_get_contents($nginxSourceFile);
        }
        file_put_contents($nginxTargetFile, $nginxContent);

        // 保存信息到配置信息
        $prefix = $command === 'up' ? 'install' : 'uninstall';
        $updateConfig = ['status' => $prefix . 'ing'];
        $updateConfig[$prefix . '_num'] = intval($appConfig[$prefix . '_num']) + 1;
        $updateConfig[$prefix . '_version'] = $versionInfo['version'];
        $updateConfig[$prefix . '_at'] = date('Y-m-d H:i:s');
        self::saveAppConfig($appName, $updateConfig);

        // 执行docker-compose命令
        $curlPath = "app/{$command}/{$appName}";
        if ($command === 'up') {
            $curlPath .= "?callback_url=" . urlencode("http://nginx/api/apps/install/callback?install_num=" . $updateConfig[$prefix . '_num']);
        }
        $res = self::curl($curlPath);
        if (Base::isError($res)) {
            self::saveAppConfig($appName, ['status' => 'error']);
            return $res;
        }
        $RESULTS['compose'] = $res['data'];

        // 返回结果
        return Base::retSuccess("success", $RESULTS);
    }

    /**
     * 执行docker-compose down命令
     *
     * @param string $appName
     * @param string $version
     * @return array
     */
    public static function dockerComposeDown(string $appName, string $version = 'latest'): array
    {
        $res = self::dockerComposeUp($appName, $version, 'down');
        if (Base::isError($res)) {
            return $res;
        }

        // 最后一步处理
        return self::dockerComposeFinalize($appName, 'not_installed');
    }

    /**
     * 更新docker-compose状态（安装或卸载之后最后一步处理）
     *
     * @param string $appName
     * @param string $status
     * @return array
     */
    public static function dockerComposeFinalize(string $appName, string $status): array
    {
        // 获取当前应用信息
        $appInfo = self::getAppConfig($appName);

        // 只有在安装中的状态才能更新
        if (!in_array($appInfo['status'], ['installing', 'uninstalling'])) {
            return Base::retError('当前状态不允许更新');
        }

        // 保存配置
        if (!self::saveAppConfig($appName, ['status' => $status])) {
            return Base::retError('更新状态失败');
        }

        // 处理安装成功或卸载成功后的操作
        $message = '更新成功';
        if ($status == 'installed') {
            // 处理安装成功后的操作
            $message = '安装成功';
        } elseif ($status == 'not_installed') {
            // 处理卸载成功后的操作
            $message = '卸载成功';
        }

        // 返回结果
        return Base::retSuccess($message);
    }

    /**
     * 获取应用信息
     *
     * @param string $appName 应用名称
     * @return array
     */
    public static function getAppInfo(string $appName): array
    {
        $baseDir = base_path('docker/appstore/apps/' . $appName);
        $info = [
            'name' => $appName,
            'description' => '',
            'tags' => [],
            'icon' => self::processAppIcon($appName, ['logo.svg', 'logo.png', 'icon.svg', 'icon.png']),
            'author' => '',
            'website' => '',
            'github' => '',
            'document' => '',
            'fields' => [],
            'require_uninstalls' => [],
        ];

        if (file_exists($baseDir . '/config.yml')) {
            $configData = Yaml::parseFile($baseDir . '/config.yml');

            // 处理名称
            if (isset($configData['name'])) {
                $info['name'] = self::getMultiLanguageField($configData['name']) ?: $appName;
            }

            // 处理描述
            if (isset($configData['description'])) {
                $info['description'] = self::getMultiLanguageField($configData['description']);
            }

            // 处理标签
            if (isset($configData['tags']) && is_array($configData['tags'])) {
                foreach ($configData['tags'] as $tag) {
                    if (trim($tag)) {
                        $info['tags'][] = trim($tag);
                    }
                }
            }

            // 处理字段
            if (isset($configData['fields']) && is_array($configData['fields'])) {
                $appConfig = self::getAppConfig($appName);

                $fields = [];
                foreach ($configData['fields'] as $field) {
                    // 检查必需的name字段及其格式
                    if (!isset($field['name'])) {
                        continue;
                    }

                    // 验证字段名格式，必须以字母或下划线开头，只允许大小写字母、数字和下划线
                    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field['name'])) {
                        continue;
                    }

                    // 标准化字段结构
                    $normalizedField = [
                        'name' => $field['name'],
                        'type' => $field['type'] ?? 'text',
                        'default' => $appConfig['params'][$field['name']] ?? $field['default'] ?? '',
                        'label' => self::getMultiLanguageField($field['label'] ?? ''),
                        'placeholder' => self::getMultiLanguageField($field['placeholder'] ?? ''),
                        'required' => $field['required'] ?? false,
                    ];

                    // 处理默认值
                    if ($normalizedField['type'] === 'number') {
                        $normalizedField['default'] = intval($normalizedField['default']);
                    }

                    // 处理 select 类型的选项
                    if ($normalizedField['type'] === 'select' && isset($field['options']) && is_array($field['options'])) {
                        $selectOptions = [];
                        foreach ($field['options'] as $option) {
                            $selectOptions[] = [
                                'label' => self::getMultiLanguageField($option['label'] ?? ''),
                                'value' => $option['value'] ?? '',
                            ];
                        }
                        $normalizedField['options'] = $selectOptions;
                    }

                    // 处理其他属性
                    foreach ($field as $key => $value) {
                        if (!in_array($key, ['name', 'type', 'default', 'label', 'placeholder', 'required', 'options'])) {
                            $normalizedField[$key] = $value;
                        }
                    }

                    $fields[] = $normalizedField;
                }
                $info['fields'] = $fields;
            }

            // 处理 require_uninstalls 配置
            if (isset($configData['require_uninstalls']) && is_array($configData['require_uninstalls'])) {
                $requireUninstalls = [];
                foreach ($configData['require_uninstalls'] as $item) {
                    // 处理不同格式的配置
                    if (is_array($item)) {
                        // 完整格式: {version: '2.0.0', reason: '原因'}
                        if (isset($item['version']) && preg_match('/^\s*([<>=!]*)\s*(.+)$/', $item['version'], $matches)) {
                            $requireUninstalls[] = [
                                'version' => $matches[2],
                                'operator' => $matches[1] ?: '=',
                                'reason' => self::getMultiLanguageField($item['reason'])
                            ];
                        }
                    } else {
                        // 简化格式: 直接是版本号字符串
                        $requireUninstalls[] = [
                            'version' => $item,
                            'operator' => '=',
                            'reason' => ''
                        ];
                    }
                }
                $info['require_uninstalls'] = $requireUninstalls;
            }

            // 处理其他标准字段
            foreach (['author', 'website', 'github', 'document'] as $field) {
                if (isset($configData[$field])) {
                    $info[$field] = $configData[$field];
                }
            }
        }

        return $info;
    }

    /**
     * 获取应用的入口点配置
     *
     * @param string|null $appName 应用名称，为null时获取所有已安装应用的入口点
     * @return array
     */
    public static function getAppEntryPoints(?string $appName = null): array
    {
        if ($appName !== null) {
            return self::entryGetSingle($appName);
        }
        return self::entryGetAll();
    }

    /**
     * 获取单个应用的入口点配置
     *
     * @param string $appName 应用名称
     * @return array
     */
    private static function entryGetSingle(string $appName): array
    {
        $baseDir = base_path('docker/appstore/apps/' . $appName);
        $entryPoints = [];

        if (!file_exists($baseDir . '/config.yml')) {
            return Base::retSuccess("success", $entryPoints);
        }

        try {
            $configData = Yaml::parseFile($baseDir . '/config.yml');
            if (isset($configData['entry_points']) && is_array($configData['entry_points'])) {
                foreach ($configData['entry_points'] as $entry) {
                    $normalizedEntry = self::entryNormalize($entry, $appName);
                    if ($normalizedEntry) {
                        $entryPoints[] = $normalizedEntry;
                    }
                }
            }
        } catch (ParseException $e) {
            return Base::retError('配置文件解析失败：' . $e->getMessage());
        }

        return Base::retSuccess("success", $entryPoints);
    }

    /**
     * 获取所有已安装应用的入口点配置
     *
     * @return array
     */
    private static function entryGetAll(): array
    {
        $allEntryPoints = [];
        $baseDir = base_path('docker/appstore/apps');

        if (!is_dir($baseDir)) {
            return Base::retSuccess("success", $allEntryPoints);
        }

        $dirs = scandir($baseDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
                continue;
            }

            if (!self::isInstalled($dir)) {
                continue;
            }

            $appEntryPoints = self::entryGetSingle($dir);
            if (Base::isSuccess($appEntryPoints)) {
                $allEntryPoints = array_merge($allEntryPoints, $appEntryPoints['data']);
            }
        }

        return Base::retSuccess("success", $allEntryPoints);
    }

    /**
     * 标准化入口点配置
     *
     * @param array $entry 原始入口点配置
     * @param string $appName 应用名称
     * @return array|null 标准化后的入口点配置，配置无效时返回null
     */
    private static function entryNormalize(array $entry, string $appName): ?array
    {
        // 检查必需的字段
        if (!isset($entry['location']) || !isset($entry['url'])) {
            return null;
        }

        // 基础配置
        $normalizedEntry = [
            'app_name' => $appName,
            'location' => $entry['location'],
            'url' => $entry['url'],
            'key' => $entry['key'] ?? substr(md5($entry['url']), 0, 16),
            'icon' => self::processAppIcon($appName, [$entry['icon'] ?? '']),
            'label' => self::getMultiLanguageField($entry['label'] ?? ''),
        ];

        // 处理可选的UI配置
        $optionalConfigs = ['transparent', 'keepAlive'];
        foreach ($optionalConfigs as $config) {
            if (isset($entry[$config])) {
                $normalizedEntry[$config] = $entry[$config];
            }
        }

        return $normalizedEntry;
    }

    /**
     * 获取应用配置信息
     *
     * @param string $appName 应用名称
     * @return array 应用配置信息
     */
    public static function getAppConfig(string $appName): array
    {
        $baseDir = base_path('docker/appstore/configs/' . $appName);
        $configFile = $baseDir . '/config.json';

        $defaultInfo = [
            'install_at' => '', // 最后一次安装的时间
            'install_num' => 0, // 安装的次数
            'install_version' => '', // 最后一次安装的版本
            'status' => 'not_installed', // 应用状态: installing, installed, uninstalling, not_installed, error
            'params' => [], // 用户自定义参数值
            'resources' => [
                'cpu_limit' => '', // CPU限制，例如 '0.5' 或 '2'
                'memory_limit' => '' // 内存限制，例如 '512M' 或 '2G'
            ],
        ];

        if (file_exists($configFile)) {
            $appConfig = json_decode(file_get_contents($configFile), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($appConfig)) {
                $defaultInfo = array_merge($defaultInfo, $appConfig);
            }
        } else {
            Base::makeDir($baseDir);
            file_put_contents($configFile, json_encode($defaultInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // 确保 status 状态
        if (!in_array($defaultInfo['status'], ['installing', 'installed', 'uninstalling', 'not_installed', 'error'])) {
            $defaultInfo['status'] = 'not_installed';
        }

        // 确保 params 是数组
        if (!is_array($defaultInfo['params'])) {
            $defaultInfo['params'] = [];
        }

        // 添加 DooTask 版本信息
        $defaultInfo['params']['DOOTASK_VERSION'] = Base::getVersion();

        // 确保 resources 完整
        if (!is_array($defaultInfo['resources'])) {
            $defaultInfo['resources'] = [];
        }
        $defaultInfo['resources']['cpu_limit'] = $defaultInfo['resources']['cpu_limit'] ?? '';
        $defaultInfo['resources']['memory_limit'] = $defaultInfo['resources']['memory_limit'] ?? '';

        // 返回应用配置信息
        return $defaultInfo;
    }

    /**
     * 保存应用配置信息
     *
     * @param string $appName 应用名称
     * @param array $data 要更新的数据
     * @param bool $merge 是否与现有配置合并，默认true
     * @return bool 保存是否成功
     */
    public static function saveAppConfig(string $appName, array $data, bool $merge = true): bool
    {
        $baseDir = base_path('docker/appstore/configs/' . $appName);
        $configFile = $baseDir . '/config.json';

        // 初始化数据
        $appConfig = [];

        // 如果需要合并，先读取现有配置
        if ($merge && file_exists($configFile)) {
            $existingData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($existingData)) {
                $appConfig = $existingData;
            }
        }

        // 更新数据
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($appConfig[$key]) && is_array($appConfig[$key])) {
                // 如果是嵌套数组，进行深度合并
                $appConfig[$key] = array_replace_recursive($appConfig[$key], $value);
            } else {
                // 普通值直接覆盖
                $appConfig[$key] = $value;
            }
        }

        // 确保目录存在
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // 写入文件
        return (bool)file_put_contents(
            $configFile,
            json_encode($appConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * 获取应用的日志
     *
     * @param string $appName
     * @param int $lines 获取的行数，默认50行, 最大2000行
     * @return array
     */
    public static function getAppLog(string $appName, int $lines = 50): array
    {
        // 日志文件路径
        $logFile = base_path('docker/appstore/logs/' . $appName . '.log');

        // 检查日志文件是否存在
        if (!file_exists($logFile)) {
            return [];
        }

        // 限制获取行数
        if ($lines <= 0) {
            $lines = 50;
        } else if ($lines > 2000) {
            $lines = 2000;
        }

        // 读取日志文件最后几行
        $output = [];
        $cmd = 'tail -n ' . $lines . ' ' . escapeshellarg($logFile);
        exec($cmd, $output);

        // 返回日志内容
        return $output;
    }

    /**
     * 判断应用是否已安装
     *
     * @param string $appName 应用名称
     * @return bool 如果应用已安装返回 true，否则返回 false
     */
    public static function isInstalled(string $appName): bool
    {
        $appConfig = self::getAppConfig($appName);
        return $appConfig['status'] === 'installed';
    }

    /**
     * 更新应用列表
     *
     * @return array
     */
    public static function appListUpdate(): array
    {
        // 检查是否正在更新
        $cacheKey = 'appstore_update_running';
        if (Cache::has($cacheKey)) {
            return Base::retError('应用列表正在更新中，请稍后再试');
        }
        $onFailure = function (string $message) use ($cacheKey) {
            Cache::forget($cacheKey);
            return Base::retError($message);
        };
        $onSuccess = function (string $message, array $data = []) use ($cacheKey) {
            Cache::forget($cacheKey);
            return Base::retSuccess($message, $data);
        };

        // 设置更新状态
        Cache::put($cacheKey, true, 180); // 3分钟有效期

        // 临时目录
        $tempDir = base_path('docker/appstore/temp/sources');
        $zipFile = $tempDir . '/sources.zip';

        // 清空临时目录
        if (is_dir($tempDir)) {
            Base::deleteDirAndFile($tempDir, true);
        } else {
            Base::makeDir($tempDir);
        }

        try {
            // 下载源列表
            $res = Ihttp::ihttp_request(self::$sourcesUrl);
            if (Base::isError($res)) {
                return $onFailure('下载源列表失败');
            }
            file_put_contents($zipFile, $res['data']);

            // 解压文件
            $zip = new \ZipArchive();
            if ($zip->open($zipFile) !== true) {
                return $onFailure('文件打开失败');
            }
            $zip->extractTo($tempDir);
            $zip->close();
            unlink($zipFile);

            // 遍历目录
            $dirs = scandir($tempDir);
            $results = [
                'success' => [],
                'failed' => []
            ];

            foreach ($dirs as $appName) {
                // 跳过当前目录、父目录和隐藏文件
                if ($appName === '.' || $appName === '..' || str_starts_with($appName, '.')) {
                    continue;
                }

                $sourceDir = $tempDir . '/' . $appName;
                if (!is_dir($sourceDir)) {
                    continue;
                }

                // 检查config.yml文件
                $configFile = $sourceDir . '/config.yml';
                if (!file_exists($configFile)) {
                    $results['failed'][] = [
                        'app_name' => $appName,
                        'reason' => '未找到config.yml配置文件'
                    ];
                    continue;
                }

                // 解析配置文件
                try {
                    $configData = Yaml::parseFile($configFile);
                } catch (ParseException $e) {
                    $results['failed'][] = [
                        'app_name' => $appName,
                        'reason' => 'YAML解析失败：' . $e->getMessage()
                    ];
                    continue;
                }

                // 检查name字段
                if (empty($configData['name'])) {
                    $results['failed'][] = [
                        'app_name' => $appName,
                        'reason' => '配置文件不正确'
                    ];
                    continue;
                }

                // 使用目录名作为应用名称
                $targetDir = base_path('docker/appstore/apps/' . $appName);

                // 复制目录
                if (!self::copyDirAndFile($sourceDir, $targetDir, true)) {
                    $results['failed'][] = [
                        'app_name' => $appName,
                        'reason' => '复制文件失败'
                    ];
                    continue;
                }

                $results['success'][] = [
                    'app_name' => $appName
                ];
            }

            // 清理临时目录
            Base::deleteDirAndFile($tempDir, true);
            return $onSuccess('更新成功', $results);
        } catch (\Exception $e) {
            // 清理临时目录
            Base::deleteDirAndFile($tempDir, true);
            return $onFailure('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 下载应用
     *
     * @param string $url 应用url
     * @return array 下载结果
     */
    public static function downloadApp(string $url): array
    {
        // 检查是否正在下载
        $cacheKey = 'appstore_download_running';
        if (Cache::has($cacheKey)) {
            return Base::retError('应用正在下载中，请稍后再试');
        }
        $onFailure = function (string $message) use ($cacheKey) {
            Cache::forget($cacheKey);
            return Base::retError($message);
        };
        $onSuccess = function (string $message, array $data = []) use ($cacheKey) {
            Cache::forget($cacheKey);
            return Base::retSuccess($message, $data);
        };

        // 设置下载状态
        Cache::put($cacheKey, true, 180); // 3分钟有效期

        // 验证URL格式
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $onFailure('URL格式不正确');
        }

        // 验证URL协议
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https', 'git'])) {
            return $onFailure('不支持的URL协议，仅支持http、https和git协议');
        }

        // 临时目录
        $tempDir = base_path('docker/appstore/temp/' . md5($url));

        // 清空临时目录
        if (is_dir($tempDir)) {
            Base::deleteDirAndFile($tempDir, true);
        } else {
            Base::makeDir($tempDir);
        }

        // 判断URL类型
        $isGit = str_ends_with($url, '.git') || str_contains($url, 'github.com') || str_contains($url, 'gitlab.com');

        try {
            if ($isGit) {
                // 克隆Git仓库
                $cmd = sprintf('cd %s && git clone --depth=1 %s .', escapeshellarg($tempDir), escapeshellarg($url));
                exec($cmd, $output, $returnVar);
                if ($returnVar !== 0) {
                    return $onFailure('Git克隆失败');
                }
            } else {
                // 下载ZIP文件
                $zipFile = $tempDir . '/app.zip';
                $res = Ihttp::ihttp_request($url);
                if (Base::isError($res)) {
                    return $onFailure('下载失败');
                }
                file_put_contents($zipFile, $res['data']);

                // 解压ZIP文件
                $zip = new \ZipArchive();
                if ($zip->open($zipFile) !== true) {
                    return $onFailure('文件打开失败');
                }
                $zip->extractTo($tempDir);
                $zip->close();
                unlink($zipFile);
            }

            // 检查config.yml文件
            $configFile = $tempDir . '/config.yml';
            if (!file_exists($configFile)) {
                return $onFailure('未找到config.yml配置文件');
            }

            // 解析配置文件
            try {
                $configData = Yaml::parseFile($configFile);
            } catch (ParseException $e) {
                return $onFailure('YAML解析失败：' . $e->getMessage());
            }

            // 检查name字段
            if (empty($configData['name'])) {
                return $onFailure('配置文件不正确');
            }

            // 处理应用名称
            $appName = Base::camel2snake(Base::cn2pinyin($configData['name'], '_'));
            if (in_array($appName, self::$protectedServiceNames)) {
                return Base::retError('服务名称 "' . $name . '" 被保护，不能使用');
            }
            $targetDir = base_path('docker/appstore/apps/' . $appName);
            $targetConfigFile = $targetDir . '/config.json';

            // 检查目标是否存在
            if (file_exists($targetConfigFile)) {
                $targetConfigData = json_decode(file_get_contents($targetConfigFile), true);
                if (is_array($targetConfigData)) {
                    $status = $targetConfigData['status'];
                    $errorMessages = [
                        'installed' => '应用已存在，请先卸载后再安装',
                        'installing' => '应用正在安装中，请稍后再试',
                        'uninstalling' => '应用正在卸载中，请稍后再试'
                    ];
                    if (isset($errorMessages[$status])) {
                        return $onFailure($errorMessages[$status]);
                    }
                }
                Base::deleteDirAndFile($targetDir);
            }

            // 移动文件到目标目录
            if (!rename($tempDir, $targetDir)) {
                return $onFailure('移动文件失败');
            }

            return $onSuccess('下载成功', ['app_name' => $appName]);
        } catch (\Exception $e) {
            // 清理临时目录
            Base::deleteDirAndFile($tempDir, true);
            return $onFailure('下载失败：' . $e->getMessage());
        }
    }

    /**
     * 获取应用的文档（README）
     *
     * @param string $appName 应用名称
     * @return string 文档内容，如果未找到则返回空字符串
     */
    private static function getAppDocument(string $appName): string
    {
        $baseDir = base_path('docker/appstore/apps/' . $appName);
        $lang = strtoupper(Base::headerOrInput('language'));

        // 使用 glob 遍历目录
        $files = glob($baseDir . '/*');

        // 正则模式，包括语言特定和通用的 README 文件
        $readmePatterns = [
            "/^README(_|-|\.)?{$lang}\.md$/i",  // README_zh.md, README-zh.md, README.zh.md
        ];
        if ($lang == 'ZH') {
            $readmePatterns[] = "/^README(_|-|\.)?CN\.md$/i"; // README_CN.md, README-cn.md, README.cn.md
        }
        if ($lang == 'ZH-CHT') {
            $readmePatterns[] = "/^README(_|-|\.)?TW\.md$/i"; // README_TW.md, README-tw.md, README.tw.md
        }
        $readmePatterns[] = "/^README\.md$/i"; // README.md

        // 遍历所有 README 模式进行匹配
        foreach ($readmePatterns as $pattern) {
            foreach ($files as $filePath) {
                $fileName = basename($filePath);
                if (preg_match($pattern, $fileName)) {
                    return file_get_contents($filePath);
                }
            }
        }

        return '';
    }

    /**
     * 检查文件名是否匹配 README 模式
     *
     * @param string $fileName 文件名
     * @param array $patterns 正则模式数组
     * @return bool 是否匹配
     */
    private static function matchReadmePattern(string $fileName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fileName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 生成docker-compose.yml文件配置
     *
     * @param string $filePath docker-compose.yml文件路径
     * @param array $params 可选参数，替换docker-compose.yml中的${XXX}变量，示例：{'APP_ID' => '123'}
     * @param array $resources 可选资源限制，示例：{'cpu_limit' => '0.5', 'memory_limit' => '512M'}
     * @return array
     */
    private static function generateDockerComposeYml(string $filePath, array $params = [], array $resources = []): array
    {
        // 应用名称
        $appName = basename(dirname($filePath, 2));

        // 服务名称
        $serviceName = 'dootask-app-' . $appName;

        // 网络名称
        $networkName = 'dootask-networks-' . env('APP_ID');

        // 主机路径
        $hostPwd = '${HOST_PWD}/docker/appstore/apps/' . $appName . '/' . basename(dirname($filePath));

        // 保存路径
        $savePath = base_path('docker/appstore/configs/' . $appName . '/docker-compose.yml');

        try {
            // 读取文件内容
            $fileContent = file_get_contents($filePath);

            // 处理特殊环境变量
            $fileContent = str_replace('${HOST_PWD}', '', $fileContent);
            $fileContent = str_replace('${PUBLIC_PATH}', '${HOST_PWD}/public', $fileContent);

            // 解析YAML文件
            $content = Yaml::parse($fileContent);

            // 确保services部分存在
            if (!isset($content['services'])) {
                return Base::retError('Docker compose文件缺少services配置');
            }

            // 设置服务名称
            $content['name'] = $serviceName;

            // 删除所有现有网络配置
            unset($content['networks']);

            // 添加网络配置
            $content['networks'][$networkName] = ['external' => true];

            // 检查服务名称是否被保护
            foreach ($content['services'] as $name => $service) {
                if (in_array($name, self::$protectedServiceNames)) {
                    return Base::retError('服务名称 "' . $name . '" 被保护，不能使用');
                }
            }

            foreach ($content['services'] as &$service) {
                // 确保所有服务都有网络配置
                $service['networks'] = [$networkName];

                // 处理现有的volumes配置
                if (isset($service['volumes'])) {
                    $service['volumes'] = self::volumeProcessConfigurations($service['volumes'], $hostPwd);
                }

                // 处理资源限制
                if (isset($resources['cpu_limit']) && $resources['cpu_limit']) {
                    $service['deploy']['resources']['limits']['cpus'] = $resources['cpu_limit'];
                }
                if (isset($resources['memory_limit']) && $resources['memory_limit']) {
                    $service['deploy']['resources']['limits']['memory'] = $resources['memory_limit'];
                }
            }

            // 生成YAML内容
            $yamlContent = Yaml::dump($content, 8, 2);

            // 替换${XXX}格式变量
            $yamlContent = preg_replace_callback('/\$\{(.*?)}/', function ($matches) use ($params) {
                return $params[$matches[1]] ?? $matches[0];
            }, $yamlContent);

            // 保存文件
            if (file_put_contents($savePath, $yamlContent) === false) {
                return Base::retError('无法写入配置文件');
            }

            // 返回成功
            return Base::retSuccess('success', [
                'file' => $savePath,
            ]);
        } catch (ParseException $e) {
            // YAML解析错误
            return Base::retError('YAML parsing error:' . $e->getMessage());
        }
    }

    /**
     * 处理卷挂载配置
     *
     * @param array $volumes 原始卷挂载配置数组
     * @param string $hostPwd 主机工作目录路径
     * @return array 处理后的卷挂载配置数组
     */
    public static function volumeProcessConfigurations(array $volumes, string $hostPwd): array
    {
        return array_map(function ($volume) use ($hostPwd) {
            // 短语法格式：字符串形式如 "./src:/app"
            if (is_string($volume)) {
                return self::volumeProcessShortSyntax($volume, $hostPwd);
            } // 长语法格式：数组形式包含 source 键
            elseif (is_array($volume) && isset($volume['source'])) {
                return self::volumeProcessLongSyntax($volume, $hostPwd);
            }
            // 其他格式保持不变
            return $volume;
        }, $volumes);
    }

    /**
     * 处理短语法格式的卷挂载
     *
     * @param string $volume 原始卷配置字符串
     * @param string $hostPwd 主机工作目录路径
     * @return string 处理后的卷配置字符串
     */
    private static function volumeProcessShortSyntax(string $volume, string $hostPwd): string
    {
        $parts = explode(':', $volume, 3);
        $sourcePath = $parts[0];

        $newSourcePath = self::volumeConvertPath($sourcePath, $hostPwd);

        // 如果路径已更改，重建挂载配置
        if ($newSourcePath !== $sourcePath) {
            $parts[0] = $newSourcePath;
            return implode(':', $parts);
        }

        return $volume;
    }

    /**
     * 处理长语法格式的卷挂载
     *
     * @param array $volume 原始卷配置数组
     * @param string $hostPwd 主机工作目录路径
     * @return array 处理后的卷配置数组
     */
    private static function volumeProcessLongSyntax(array $volume, string $hostPwd): array
    {
        $newSourcePath = self::volumeConvertPath($volume['source'], $hostPwd);

        // 如果路径已更改，更新source
        if ($newSourcePath !== $volume['source']) {
            $volume['source'] = $newSourcePath;
        }

        return $volume;
    }

    /**
     * 将相对路径转换为绝对路径
     *
     * @param string $path 原始路径
     * @param string $hostPwd 主机工作目录路径
     * @return string 处理后的绝对路径
     */
    private static function volumeConvertPath(string $path, string $hostPwd): string
    {
        // 判断是否为相对路径，Docker卷挂载有以下几种情况：
        // 1. 环境变量路径：包含${PWD}的路径，如 "${PWD}/data:/app/data"
        //    这也是一种相对路径表示方式，需要标准化处理
        // 2. 显式相对路径：以./或../开头的路径，如 "./data:/app/data" 或 "../logs:/var/log"
        // 3. 隐式相对路径：不以/开头，但中间包含/的路径，如 "data/logs:/app/logs"
        //    (纯名称如"data"没有/，通常是命名卷而非相对路径)
        // 4. 绝对路径：以/开头的路径，如 "/var/run/docker.sock:/var/run/docker.sock"
        //    绝对路径已经是完整路径，不需要转换
        //
        // 注：Docker Compose在解析路径时，相对路径是相对于docker-compose.yml文件所在目录的
        // 我们需要将这些相对路径转换为绝对路径，以确保在不同环境中能正确挂载目录

        // 处理${PWD}路径
        if (str_contains($path, '${PWD}')) {
            // 将${PWD}替换为我们的标准路径格式
            return str_replace('${PWD}', $hostPwd, $path);
        }

        // 处理./或../开头的显式相对路径
        if (str_starts_with($path, './') || str_starts_with($path, '../')) {
            return $hostPwd . '/' . ltrim($path, './');
        }

        // 处理不以/开头的路径，且要么包含/（明确是路径而非命名卷）
        if (!str_starts_with($path, '/') && str_contains($path, '/')) {
            return $hostPwd . '/' . $path;
        }

        // 命名卷或绝对路径，保持不变
        return $path;
    }

    /**
     * 获取多语言字段
     *
     * @param mixed $field 要处理的字段值
     * @return mixed 处理后的字段值
     */
    private static function getMultiLanguageField(mixed $field): mixed
    {
        // 判断单语言字段，直接返回
        if (!is_array($field)) {
            return $field;
        }

        // 空数组检查
        if (empty($field)) {
            return "";
        }

        $lang = Base::headerOrInput('language');

        // 使用null合并运算符简化
        return $field[$lang] ?? $field[array_key_first($field)];
    }

    /**
     * 处理应用图标
     *
     * @param string $appName 应用名称
     * @param array $iconFiles 待处理的图标文件名数组
     * @return string 处理后的图标URL，如果没有可用图标则返回空字符串
     */
    private static function processAppIcon(string $appName, array $iconFiles): string
    {
        $baseDir = base_path('docker/appstore/apps/' . $appName);
        $iconDir = public_path('uploads/file/appstore/' . $appName);

        foreach ($iconFiles as $iconFile) {
            // 如果图标为空，则跳过
            if (empty($iconFile)) {
                continue;
            }

            // 检查是否为URL方式（以http或https开头）
            if (preg_match('/^https?:\/\//i', $iconFile)) {
                return $iconFile;
            }

            // 处理图标文件路径
            $iconFile = preg_replace('/^\/|^(\.\.\/)+|\.\//i', '', $iconFile);
            $iconPath = $baseDir . '/' . $iconFile;

            if (file_exists($iconPath)) {
                // 创建目标目录、路径
                $targetName = str_replace(['/', '\\'], '_', $iconFile);
                $targetFile = $iconDir . '/' . $targetName;

                // 判断目标文件是否存在，或源文件是否比目标文件新
                if (!file_exists($targetFile) || filemtime($iconPath) > filemtime($targetFile)) {
                    Base::makeDir($iconDir);
                    copy($iconPath, $targetFile);
                }

                // 返回图标URL
                return Base::fillUrl('uploads/file/appstore/' . $appName . '/' . $targetName);
            }
        }

        return '';
    }

    /**
     * 获取应用的可用版本列表
     *
     * @param string $appName 应用名称
     * @return array 按语义化版本排序（从新到旧）的版本号数组
     */
    private static function getAvailableVersions(string $appName): array
    {
        $baseDir = base_path('docker/appstore/apps/' . $appName);
        $versions = [];

        // 检查应用目录是否存在
        if (!is_dir($baseDir)) {
            return $versions;
        }

        // 遍历应用目录
        $dirs = scandir($baseDir);
        foreach ($dirs as $dir) {
            // 跳过当前目录、父目录和隐藏文件
            if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
                continue;
            }

            $fullPath = $baseDir . '/' . $dir;

            // 检查是否为目录
            if (!is_dir($fullPath)) {
                continue;
            }

            // 检查是否存在docker-compose.yml文件
            $dockerComposeFile = $fullPath . '/docker-compose.yml';
            if (!file_exists($dockerComposeFile)) {
                continue;
            }

            // 检查目录名是否符合版本号格式 (如: 1.0.0, v1.0.0, 1.0, v1.0, 等)
            // 支持的格式: x.y.z, vx.y.z, x.y, vx.y
            if (preg_match('/^v?\d+(\.\d+){1,2}$/', $dir)) {
                $versions[] = [
                    'version' => $dir,
                ];
            }
        }

        // 按版本号排序（从新到旧）
        usort($versions, function ($a, $b) {
            // 将版本号拆分为数组
            $partsA = explode('.', $a['version']);
            $partsB = explode('.', $b['version']);

            // 比较主版本号
            if ($partsA[0] != $partsB[0]) {
                return $partsB[0] <=> $partsA[0]; // 降序排列
            }

            // 比较次版本号（如果存在）
            if (isset($partsA[1]) && isset($partsB[1]) && $partsA[1] != $partsB[1]) {
                return $partsB[1] <=> $partsA[1];
            }

            // 比较修订版本号（如果存在）
            if (isset($partsA[2]) && isset($partsB[2])) {
                return $partsB[2] <=> $partsA[2];
            } elseif (isset($partsA[2])) {
                return -1; // A有修订版本号，B没有
            } elseif (isset($partsB[2])) {
                return 1;  // B有修订版本号，A没有
            }

            return 0; // 版本号相同
        });

        return $versions;
    }

    /**
     * 复制目录和文件
     *
     * @param string $sourceDir 源目录
     * @param string $targetDir 目标目录
     * @param bool $recursive 是否递归复制
     * @return bool
     */
    private static function copyDirAndFile(string $sourceDir, string $targetDir, bool $recursive = false): bool
    {
        if (!is_dir($sourceDir)) {
            return false;
        }

        // 创建目标目录
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                return false;
            }
        }

        // 打开源目录
        $dir = opendir($sourceDir);
        if (!$dir) {
            return false;
        }

        // 遍历源目录
        while (($file = readdir($dir)) !== false) {
            // 跳过当前目录和父目录
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourceFile = $sourceDir . '/' . $file;
            $targetFile = $targetDir . '/' . $file;

            if (is_dir($sourceFile)) {
                // 如果是目录且需要递归复制
                if ($recursive) {
                    if (!self::copyDirAndFile($sourceFile, $targetFile, true)) {
                        closedir($dir);
                        return false;
                    }
                }
            } else {
                // 复制文件
                if (!copy($sourceFile, $targetFile)) {
                    closedir($dir);
                    return false;
                }
                // 保持文件权限
                chmod($targetFile, fileperms($sourceFile));
            }
        }

        closedir($dir);
        return true;
    }

    /**
     * 执行curl请求
     *
     * @param $path
     * @return array
     */
    private static function curl($path): array
    {
        $url = "http://appstore/api/{$path}";
        $extra = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . md5(env('APP_KEY')),
        ];

        // 执行请求
        $res = Ihttp::ihttp_request($url, [], $extra);
        if (Base::isError($res)) {
            return Base::retError("请求错误", $res);
        }

        // 解析响应
        $resData = Base::json2array($res['data']);
        if ($resData['code'] != 200) {
            return Base::retError("请求失败", $resData);
        }

        // 返回结果
        return Base::retSuccess("success", $resData['data']);
    }
}
