<?php

namespace App\Module\Apps;

class Volumes
{
    /**
     * 处理卷挂载配置，将相对路径转换为绝对路径
     *
     * @param array $volumes 原始卷挂载配置
     * @param string $appName 应用名称
     * @return array 处理后的卷挂载配置
     */
    public static function processVolumeConfigurations(array $volumes, string $appName): array
    {
        return array_map(function ($volume) use ($appName) {
            // 短语法格式：字符串形式如 "./src:/app"
            if (is_string($volume)) {
                return self::processShortSyntaxVolume($volume, $appName);
            } // 长语法格式：数组形式包含 source 键
            elseif (is_array($volume) && isset($volume['source'])) {
                return self::processLongSyntaxVolume($volume, $appName);
            }
            // 其他格式保持不变
            return $volume;
        }, $volumes);
    }

    /**
     * 处理短语法格式的卷挂载
     *
     * @param string $volume 原始卷配置字符串
     * @param string $appName 应用名称
     * @return string 处理后的卷配置字符串
     */
    private static function processShortSyntaxVolume(string $volume, string $appName): string
    {
        $parts = explode(':', $volume, 3);
        $sourcePath = $parts[0];

        $newSourcePath = self::convertRelativePathToAbsolute($sourcePath, $appName);

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
     * @param string $appName 应用名称
     * @return array 处理后的卷配置数组
     */
    private static function processLongSyntaxVolume(array $volume, string $appName): array
    {
        $newSourcePath = self::convertRelativePathToAbsolute($volume['source'], $appName);

        // 如果路径已更改，更新source
        if ($newSourcePath !== $volume['source']) {
            $volume['source'] = $newSourcePath;
        }

        return $volume;
    }

    /**
     * 获取应用的基础路径
     *
     * @param string $appName 应用名称
     * @return string 应用的基础路径
     */
    private static function getBaseAppPath(string $appName): string
    {
        return '${HOST_PWD}/docker/apps/' . $appName;
    }

    /**
     * 将相对路径转换为绝对路径
     *
     * @param string $path 原始路径
     * @param string $appName 应用名称
     * @return string 处理后的路径
     */
    private static function convertRelativePathToAbsolute(string $path, string $appName): string
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

        // 获取基础应用路径
        $baseAppPath = self::getBaseAppPath($appName);

        // 处理${PWD}路径
        if (str_contains($path, '${PWD}')) {
            // 将${PWD}替换为我们的标准路径格式
            return str_replace('${PWD}', $baseAppPath, $path);
        }

        // 处理./或../开头的显式相对路径
        if (str_starts_with($path, './') || str_starts_with($path, '../')) {
            $cleanPath = ltrim($path, './');
            return $baseAppPath . '/' . $cleanPath;
        }

        // 处理不以/开头的路径，且要么包含/（明确是路径而非命名卷）
        if (!str_starts_with($path, '/') && str_contains($path, '/')) {
            return $baseAppPath . '/' . $path;
        }

        // 命名卷或绝对路径，保持不变
        return $path;
    }
}
