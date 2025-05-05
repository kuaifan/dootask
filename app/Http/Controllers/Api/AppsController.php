<?php

namespace App\Http\Controllers\Api;


use App\Module\Apps\Apps;

/**
 * @apiDefine apps
 *
 * 应用相关接口
 */
class AppsController extends AbstractController
{

    public function up()
    {
        $appName = 'MysqlExposePort';
        return Apps::dockerComposeUp($appName);
    }

    public function down()
    {
        $appName = 'MysqlExposePort';
        return Apps::dockerComposeUp($appName, 'down');
    }
}
