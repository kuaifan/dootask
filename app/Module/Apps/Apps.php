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
        'okr',
        'face',
        'search',
    ];

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

        // 保存版本信息
        file_put_contents($versionInfo['base_dir'] . '/latest', $versionInfo['version']);

        // 生成docker-compose.yml文件
        $res = self::generateDockerComposeYml($versionInfo['compose_file'], [
            'PROXY_PORT' => '33062',    // todo 参数自定义
        ]);
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
        $savePath = dirname($filePath) . '/docker-compose.doo.yml';

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
            $yamlContent = preg_replace_callback('/\$\{(.*?)\}/', function ($matches) use ($params) {
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
