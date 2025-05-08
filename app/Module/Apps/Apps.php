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
        $result = [];

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

        // 保存版本信息到.applocal文件
        self::saveAppLocalInfo($appName, [
            'installed_version' => $versionInfo['version'],
            'installed_at' => date('Y-m-d H:i:s')
        ]);
        $params = self::getAppLocalInfo($appName)['params'] ?? [];

        // 生成docker-compose.yml文件
        $res = self::generateDockerComposeYml($versionInfo['compose_file'], $params);
        if (Base::isError($res)) {
            return $res;
        }
        $result['generate'] = $res['data'];

        // 执行docker-compose命令
        $res = self::curl("apps/{$command}/{$appName}");
        if (Base::isError($res)) {
            return $res;
        }
        $result['compose'] = $res['data'];

        // nginx配置文件处理
        $nginxFile = $versionInfo['path'] . '/nginx.conf';
        $nginxTarget = base_path('docker/nginx/apps/' . $appName . '.conf');
        if (file_exists($nginxTarget)) {
            unlink($nginxTarget);
        }
        if (file_exists($nginxFile)) {
            if ($command === 'up') {
                copy($nginxFile, $nginxTarget);
            }
            // 重启nginx
            $res = self::curl("nginx/reload");
            if (Base::isError($res)) {
                return $res;
            }
            $result['nginx'] = $res['data'];
        }

        // 返回结果
        return Base::retSuccess("success", $result);
    }

    /**
     * 执行docker-compose down命令
     * @param string $appName
     * @param string $version
     * @return array
     */
    public static function dockerComposeDown(string $appName, string $version = 'latest'): array
    {
        return self::dockerComposeUp($appName, $version, 'down');
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
            'icon' => '',
            'author' => '',
            'website' => '',
            'github' => '',
            'document' => '',
            'fields' => [],
        ];

        // 处理应用图标
        $iconFiles = ['logo.svg', 'logo.png', 'icon.svg', 'icon.png'];
        foreach ($iconFiles as $iconFile) {
            $iconPath = $baseDir . '/' . $iconFile;
            if (file_exists($iconPath)) {
                // 创建目标目录、路径
                $targetDir = public_path('uploads/file/apps/' . $appName);
                $targetFile = $targetDir . '/' . $iconFile;

                // 判断目标文件是否存在，或源文件是否比目标文件新
                if (!file_exists($targetFile) || filemtime($iconPath) > filemtime($targetFile)) {
                    Base::makeDir($targetDir);
                    copy($iconPath, $targetFile);
                }

                // 设置图标URL
                $info['icon'] = Base::fillUrl('uploads/file/apps/' . $appName . '/' . $iconFile);
                break;
            }
        }

        if (file_exists($baseDir . '/config.yml')) {
            $configData = Yaml::parseFile($baseDir . '/config.yml');

            // 处理基础字段
            if (isset($configData['name'])) {
                $info['name'] = $configData['name'];
            }

            // 处理描述（支持多语言）
            if (isset($configData['description'])) {
                $info['description'] = self::formatMultiLanguageField($configData['description']);
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
                        'label' => [],
                        'placeholder' => [],
                    ];

                    // 处理label（支持多语言）
                    if (isset($field['label'])) {
                        $normalizedField['label'] = self::formatMultiLanguageField($field['label']);
                    }

                    // 处理placeholder（支持多语言）
                    if (isset($field['placeholder'])) {
                        $normalizedField['placeholder'] = self::formatMultiLanguageField($field['placeholder']);
                    }

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
            'created_at' => '', // 应用首次添加到系统的时间
            'installed_at' => '', // 最近一次安装/更新的时间
            'installed_version' => '', // 最近一次安装/更新的版本
            'status' => 'not_installed', // 应用状态: installed, not_installed, error
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
     * 格式化多语言字段
     *
     * @param mixed $field 要处理的字段值
     * @return array 格式化后的多语言数组
     */
    private static function formatMultiLanguageField(mixed $field): array
    {
        if (is_array($field)) {
            // 多语言字段
            $result = $field;
            $firstLang = array_key_first($result);
            $result['default'] = $result[$firstLang];
            return $result;
        } else {
            // 单语言字段
            return ['default' => $field];
        }
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
