<?php

namespace App\Module\Apps;

use App\Module\Base;
use App\Module\Ihttp;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

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
    ];

    /**
     * 获取应用列表
     * @return array
     */
    public static function appList(): array
    {
        $apps = [];
        $baseDir = base_path('docker/apps');
        $dirs = scandir($baseDir);
        foreach ($dirs as $dir) {
            // 跳过当前目录、父目录和隐藏文件
            if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
                continue;
            }
            $apps[] = [
                'name' => $dir,
                'info' => self::getAppInfo($dir),
                'local' => self::getAppLocalInfo($dir),
                'versions' => self::getAvailableVersions($dir),
            ];
        }
        return Base::retSuccess("success", $apps);
    }

    /**
     * 获取应用信息
     * @param string $appName 应用名称
     * @return array
     */
    public static function appInfo(string $appName): array
    {
        return Base::retSuccess("success", [
            'info' => self::getAppInfo($appName),
            'local' => self::getAppLocalInfo($appName),
            'versions' => self::getAvailableVersions($appName),
            'document' => self::getAppDocument($appName),
        ]);
    }

    /**
     * 执行docker-compose up命令
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

        // 获取本地安装信息
        $localInfo = self::getAppLocalInfo($appName);

        // 检查是否需要卸载旧版本
        if ($command === 'up' && $localInfo['status'] === 'installed' && $localInfo['require_uninstalls']) {
            foreach ($localInfo['require_uninstalls'] as $requireUninstall) {
                if (version_compare($versionInfo['version'], $requireUninstall['version'], $requireUninstall['operator'])) {
                    $op = $requireUninstall['operator'] ?: '=';
                    $reason = !empty($requireUninstall['reason']) ? "（原因：{$requireUninstall['reason']}）" : '';
                    return Base::retError("更新版本 {$versionInfo['version']}，需要先卸载已安装的版本{$reason}");
                }
            }
        }

        // 生成docker-compose.yml文件
        $res = self::generateDockerComposeYml($versionInfo['compose_file'], $localInfo['params']);
        if (Base::isError($res)) {
            return $res;
        }
        $RESULTS['generate'] = $res['data'];

        // 保存信息到.applocal文件
        $prefix = $command === 'up' ? 'install' : 'uninstall';
        $localUpdate = ['status' => $prefix . 'ing'];
        $localUpdate[$prefix . '_num'] = intval($localInfo[$prefix . '_num']) + 1;
        $localUpdate[$prefix . '_version'] = $versionInfo['version'];
        $localUpdate[$prefix . '_at'] = date('Y-m-d H:i:s');
        self::saveAppLocalInfo($appName, $localUpdate);

        // 执行docker-compose命令
        $curlPath = "apps/{$command}/{$appName}";
        if ($command === 'up') {
            $curlPath .= "?callback_url=" . urlencode("http://host.docker.internal:" . env("APP_PORT") . "/api/apps/install/callback?installed_num=" . $localUpdate[$prefix . '_num']);
        }
        $res = self::curl($curlPath);
        if (Base::isError($res)) {
            self::saveAppLocalInfo($appName, ['status' => 'error']);
            return $res;
        }
        $RESULTS['compose'] = $res['data'];

        // 返回结果
        return Base::retSuccess("success", $RESULTS);
    }

    /**
     * 执行docker-compose down命令
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
     * @param string $appName
     * @param string $status
     * @return array
     */
    public static function dockerComposeFinalize(string $appName, string $status): array
    {
        // 获取当前应用信息
        $appInfo = self::getAppLocalInfo($appName);

        // 只有在安装中的状态才能更新
        if (!in_array($appInfo['status'], ['installing', 'uninstalling'])) {
            return Base::retError('当前状态不允许更新');
        }

        // 保存配置
        if (!self::saveAppLocalInfo($appName, ['status' => $status])) {
            return Base::retError('更新状态失败');
        }

        // 更新nginx配置
        $res = self::nginxUpdate($appName);
        if (Base::isError($res)) {
            self::saveAppLocalInfo($appName, ['status' => 'error']);
            return $res;
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
     * 更新nginx配置
     * @param string $appName
     * @return array
     */
    public static function nginxUpdate(string $appName): array
    {
        // 获取本地安装信息
        $localInfo = self::getAppLocalInfo($appName);

        // nginx配置文件处理
        $nginxFile = base_path('docker/apps/' . $appName . '/' . $localInfo['installed_version'] . '/nginx.conf');
        $nginxTarget = base_path('docker/nginx/apps/' . $appName . '.conf');
        $needReload = false;
        if (file_exists($nginxTarget)) {
            unlink($nginxTarget);
            $needReload = true;
        }
        if (file_exists($nginxFile) && $localInfo['status'] === 'installed') {
            copy($nginxFile, $nginxTarget);
            $res = self::curl("nginx/test");
            if (Base::isError($res)) {
                unlink($nginxTarget);
                return $res;
            }
            $needReload = true;
        }

        // 重启nginx
        if ($needReload) {
            $res = self::curl("nginx/reload");
            if (Base::isError($res)) {
                return $res;
            }
        }

        // 返回结果
        return Base::retSuccess("success");
    }

    /**
     * 获取应用信息
     * @param string $appName 应用名称
     * @return array
     */
    public static function getAppInfo(string $appName): array
    {
        $baseDir = base_path('docker/apps/' . $appName);
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
                        'default' => $field['default'] ?? '',
                        'label' => self::getMultiLanguageField($field['label']),
                        'placeholder' => self::getMultiLanguageField($field['placeholder']),
                    ];

                    // 处理其他属性
                    foreach ($field as $key => $value) {
                        if (!in_array($key, ['name', 'type', 'default', 'label', 'placeholder'])) {
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
     * @param string $appName 应用名称
     * @return array
     */
    public static function getAppEntryPoints(string $appName): array
    {
        $baseDir = base_path('docker/apps/' . $appName);
        $entryPoints = [];

        if (!file_exists($baseDir . '/config.yml')) {
            return Base::retSuccess("success", $entryPoints);
        }

        $configData = Yaml::parseFile($baseDir . '/config.yml');
        if (isset($configData['entry_points']) && is_array($configData['entry_points'])) {
            foreach ($configData['entry_points'] as $entry) {
                // 检查必需的字段
                if (!isset($entry['location']) || !isset($entry['url'])) {
                    continue;
                }

                // 标准化入口点结构
                $normalizedEntry = [
                    'location' => $entry['location'],
                    'url' => $entry['url'],
                    'icon' => self::processAppIcon($appName, [$entry['icon']]),
                    'label' => self::getMultiLanguageField($entry['label']),
                ];

                // 处理可选的UI配置
                foreach (['transparent', 'keepAlive'] as $option) {
                    if (isset($entry[$option])) {
                        $normalizedEntry[$option] = $entry[$option];
                    }
                }

                $entryPoints[] = $normalizedEntry;
            }
        }

        return Base::retSuccess("success", $entryPoints);
    }

    /**
     * 获取应用的本地安装信息
     *
     * @param string $appName 应用名称
     * @return array 应用的本地安装信息
     */
    public static function getAppLocalInfo(string $appName): array
    {
        $baseDir = base_path('docker/apps/' . $appName);
        $appLocalFile = $baseDir . '/.applocal';

        $defaultInfo = [
            'installed_at' => '', // 最后一次安装的时间
            'installed_num' => 0, // 安装的次数
            'installed_version' => '', // 最后一次安装的版本
            'status' => 'not_installed', // 应用状态: installing, installed, uninstalling, not_installed, error
            'params' => [], // 用户自定义参数值
            'resources' => [
                'cpu_limit' => '', // CPU限制，例如 '0.5' 或 '2'
                'memory_limit' => '' // 内存限制，例如 '512M' 或 '2G'
            ],
        ];

        if (file_exists($appLocalFile)) {
            $localInfo = json_decode(file_get_contents($appLocalFile), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($localInfo)) {
                $defaultInfo = array_merge($defaultInfo, $localInfo);
            }
        }

        // 确保 params 是数组
        if (!is_array($defaultInfo['params'])) {
            $defaultInfo['params'] = [];
        }

        // 添加 DooTask 版本信息
        $defaultInfo['params']['DOOTASK_VERSION'] = Base::getVersion();

        return $defaultInfo;
    }

    /**
     * 保存应用的本地配置信息
     *
     * @param string $appName 应用名称
     * @param array $data 要更新的数据
     * @param bool $merge 是否与现有配置合并，默认true
     * @return bool 保存是否成功
     */
    public static function saveAppLocalInfo(string $appName, array $data, bool $merge = true): bool
    {
        $baseDir = base_path('docker/apps/' . $appName);
        $appLocalFile = $baseDir . '/.applocal';

        // 初始化数据
        $localInfo = [];

        // 如果需要合并，先读取现有配置
        if ($merge && file_exists($appLocalFile)) {
            $existingData = json_decode(file_get_contents($appLocalFile), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($existingData)) {
                $localInfo = $existingData;
            }
        }

        // 更新数据
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($localInfo[$key]) && is_array($localInfo[$key])) {
                // 如果是嵌套数组，进行深度合并
                $localInfo[$key] = array_replace_recursive($localInfo[$key], $value);
            } else {
                // 普通值直接覆盖
                $localInfo[$key] = $value;
            }
        }

        // 确保目录存在
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // 写入文件
        return (bool)file_put_contents(
            $appLocalFile,
            json_encode($localInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * 获取应用的文档（README）
     *
     * @param string $appName 应用名称
     * @return string 文档内容，如果未找到则返回空字符串
     */
    private static function getAppDocument(string $appName): string {
        $baseDir = base_path('docker/apps/' . $appName);
        $lang = Base::headerOrInput('language');

        // 使用 glob 遍历目录
        $files = glob($baseDir . '/*');

        // 正则模式，包括语言特定和通用的 README 文件
        $readmePatterns = [
            "/^README(_|-|\.)?{$lang}\.md$/i",  // README_zh.md, README-zh.md, README.zh.md
            "/^README\.md$/i",                 // README.md
            "/^readme\.md$/i",                 // readme.md
        ];

        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // 检查是否为文件且匹配 README 模式
            if (is_file($filePath) && self::matchReadmePattern($fileName, $readmePatterns)) {
                return file_get_contents($filePath);
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
    private static function matchReadmePattern(string $fileName, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fileName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 生成docker-compose.yml文件配置
     * @param string $filePath docker-compose.yml文件路径
     * @param array $params 可选参数，替换docker-compose.yml中的${XXX}变量
     * @return array
     */
    private static function generateDockerComposeYml(string $filePath, array $params = []): array
    {
        // 应用名称
        $appName = basename(dirname($filePath, 2));

        // 服务名称
        $serviceName = 'dootask-app-' . $appName;

        // 网络名称
        $networkName = 'dootask-networks-' . env('APP_ID');

        // 主机路径
        $hostPwd = '${HOST_PWD}/docker/apps/' . $appName . '/' . basename(dirname($filePath));

        // 保存路径
        $savePath = dirname($filePath) . '/.docker-compose.local.yml';

        try {
            // 解析YAML文件
            $content = Yaml::parseFile($filePath);

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
                    $service['volumes'] = Volumes::processVolumeConfigurations($service['volumes'], $hostPwd);
                }
            }

            // 生成YAML内容
            $yamlContent = Yaml::dump($content, 4, 2);

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
        $baseDir = base_path('docker/apps/' . $appName);
        $iconDir = public_path('uploads/file/apps/' . $appName);

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
                return Base::fillUrl('uploads/file/apps/' . $appName . '/' . $targetName);
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
        $baseDir = base_path('docker/apps/' . $appName);
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
                    'version' => ltrim($dir, 'v'),  // 去掉前缀v
                    'path' => $fullPath,    // 目录路径
                    'base_dir' => $baseDir, // 基础目录
                    'compose_file' => $dockerComposeFile    // docker-compose.yml文件路径
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
     * 执行curl请求
     * @param $path
     * @return array
     */
    private static function curl($path): array
    {
        $url = "http://host.docker.internal:" . env("APPS_PORT") . "/{$path}";
        $extra = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env('APP_KEY'),
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
