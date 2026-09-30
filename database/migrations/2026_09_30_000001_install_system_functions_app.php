<?php

use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Yaml\Yaml;

/**
 * 默认安装「系统功能」应用并启用全部功能。
 *
 * 应用的安装状态保存在 docker/appstore/config/system-functions/config.yml（与其它应用同源），
 * 因此这里写的是配置文件而不是数据表。仅在配置文件不存在时写入：
 * 已有配置（包括管理员卸载后留下的未安装状态、自行调整过的开关）一律不覆盖。
 */
return new class extends Migration {
    public function up(): void
    {
        $appId = 'system-functions';
        $configDir = base_path('docker/appstore/config/' . $appId);
        $configFile = $configDir . '/config.yml';
        if (file_exists($configFile)) {
            return;
        }

        if (!is_dir($configDir) && !@mkdir($configDir, 0755, true) && !is_dir($configDir)) {
            throw new RuntimeException("无法创建目录：{$configDir}");
        }

        $config = [
            'install_at' => date('Y-m-d H:i:s'),
            'install_num' => 1,
            'install_version' => $this->latestVersion($appId),
            'status' => 'installed',
            'params' => [
                'REPORT' => true,
                'CALENDAR' => true,
                'FAVORITE' => true,
                'RECENT' => true,
                'CHECKIN' => true,
            ],
            'resources' => [
                'cpu_limit' => '0',
                'memory_limit' => '0',
            ],
        ];
        if (@file_put_contents($configFile, Yaml::dump($config, 4, 4)) === false) {
            throw new RuntimeException("无法写入配置文件：{$configFile}");
        }
    }

    public function down(): void
    {
        // 不回滚：无法区分配置是本迁移写入的还是管理员后续调整的
    }

    /**
     * 应用包内的最新版本号；应用包尚未同步到本地时使用首个版本。
     */
    private function latestVersion(string $appId): string
    {
        $latest = '1.0.0';
        foreach (glob(base_path("docker/appstore/apps/{$appId}/*"), GLOB_ONLYDIR) ?: [] as $dir) {
            $version = basename($dir);
            if (preg_match('/^v?\d+(\.\d+){1,2}$/', $version) && version_compare(ltrim($version, 'v'), ltrim($latest, 'v'), '>')) {
                $latest = $version;
            }
        }
        return $latest;
    }
};
