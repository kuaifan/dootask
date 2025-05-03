<?php

namespace App\Http\Controllers\Api;


use App\Module\Docker;

/**
 * @apiDefine apps
 *
 * 应用相关接口
 */
class AppsController extends AbstractController
{

    public function test()
    {
        $dirPath = base_path('docker/apps/MysqlExposePort');
        $filePath = $dirPath . '/docker-compose.yml';
        $savePath = $dirPath . '/docker-compose-local.yml';
        return Docker::generateComposeYml($filePath, $savePath, [
            'config' => [
                'PROXY_PORT' => '33062',
            ]
        ]);
    }
}
