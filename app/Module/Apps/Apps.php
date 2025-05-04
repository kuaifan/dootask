<?php

namespace App\Module\Apps;

use App\Module\Base;
use App\Module\Ihttp;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Apps
{
    /**
     * 生成docker-compose.yml文件配置
     *
     * @param string $filePath docker-compose.yml文件路径
     * @param string|null $savePath 保存文件路径，为空则覆盖原文件
     * @param array $params 可选参数，替换docker-compose.yml中的${XXX}变量
     * @return bool 是否生成成功
     */
    public static function generateDockerComposeYml(string $filePath, ?string $savePath = null, array $params = []): bool
    {
        // 应用名称
        $appName = basename(dirname($filePath));
        $serviceName = preg_replace('/(?<!^)([A-Z])/', '-$1', $appName);
        $serviceName = 'dootask-app-' . strtolower($serviceName);

        // 网络名称
        $networkName = 'dootask-networks-' . env('APP_ID');

        try {
            // 解析YAML文件
            $content = Yaml::parseFile($filePath);

            // 确保services部分存在
            if (!isset($content['services'])) {
                return false;
            }

            // 设置服务名称
            $content['name'] = $serviceName;

            // 删除所有现有网络配置
            unset($content['networks']);

            // 添加网络配置
            $content['networks'][$networkName] = ['external' => true];

            foreach ($content['services'] as &$service) {
                // 确保所有服务都有网络配置
                $service['networks'] = [$networkName];

                // 处理现有的volumes配置
                if (isset($service['volumes'])) {
                    $service['volumes'] = Volumes::processVolumeConfigurations($service['volumes'], $appName);
                }
            }

            // 生成YAML内容
            $yamlContent = Yaml::dump($content, 4, 2);

            // 替换${XXX}格式变量
            $yamlContent = preg_replace_callback('/\$\{(.*?)\}/', function ($matches) use ($params) {
                return $params[$matches[1]] ?? $matches[0];
            }, $yamlContent);

            // 写回文件
            file_put_contents($savePath ?? $filePath, $yamlContent);

            return true;
        } catch (ParseException) {
            // YAML解析错误
            return false;
        } catch (\Exception) {
            // 其他错误
            return false;
        }
    }

    /**
     * 执行docker-compose up|down命令
     * @param string $appName
     * @param string $command
     * @return array
     */
    public static function dockerComposeUp(string $appName, string $command = 'up'): array
    {
        $url = "http://host.docker.internal:" . env("APPS_PORT") . "/apps/{$command}/{$appName}";
        $extra = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env('APP_KEY'),
        ];
        $res = Ihttp::ihttp_request($url, [], $extra);
        if (Base::isError($res)) {
            return Base::retError("请求错误", $res);
        }
        $resData = Base::json2array($res['data']);
        if ($resData['code'] != 200) {
            return Base::retError("请求失败", $resData);
        }
        return Base::retSuccess("success", $resData['data']);
    }
}
