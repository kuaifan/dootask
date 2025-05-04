<?php

namespace App\Http\Controllers\Api;


use App\Module\Apps;
use App\Module\Base;

/**
 * @apiDefine apps
 *
 * 应用相关接口
 */
class AppsController extends AbstractController
{

    public function test()
    {
        $appName = 'MysqlExposePort';
        $dirPath = base_path('docker/apps/' . $appName);
        $filePath = $dirPath . '/docker-compose.yml';
        $savePath = $dirPath . '/docker-compose-local.yml';

        $res = Apps::generateDockerComposeYml($filePath, $savePath, [
            'config' => [
                'PROXY_PORT' => '33062',
            ]
        ]);
        if (!$res) {
            return Base::retError('生成docker-compose.yml失败');
        }

        return Apps::dockerComposeUp($appName);
    }
}
