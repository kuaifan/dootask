<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Apps;
use App\Module\Base;
use App\Module\Timer;
use Cache;
use Request;

/**
 * @apiDefine apps
 *
 * 应用相关接口
 */
class AppsController extends AbstractController
{
    /**
     * @api {get} api/apps/list           01. 获取应用列表（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName list
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Array}  data    应用列表数据
     */
    public function list()
    {
        User::auth('admin');
        //
        return Apps::appList();
    }

    /**
     * @api {get} api/apps/list/update           02. 更新应用列表（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName list_update
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Array}  data    应用列表数据
     */
    public function list__update()
    {
        User::auth('admin');
        //
        return Apps::appListUpdate();
    }

    /**
     * @api {get} api/apps/info           03. 获取应用详情（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName info
     *
     * @apiParam {String} app_name      应用名称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    应用详细信息
     * @apiSuccess {Object} data.info     应用基本信息
     * @apiSuccess {Object} data.config   应用配置信息
     * @apiSuccess {Array}  data.versions 可用版本列表
     */
    public function info()
    {
        User::auth('admin');
        //
        $appName = Request::input('app_name');
        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        return Apps::appInfo($appName);
    }

    /**
     * @api {get} api/apps/status           04. 获取应用状态
     *
     * @apiDescription 获取应用状态，包括已安装的应用和应用菜单
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName status
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    应用和菜单信息
     * @apiSuccess {Array}  data.installed  已安装应用列表
     * @apiSuccess {Array}  data.menus      应用菜单列表
     */
    public function status()
    {
        User::auth();

        // 获取已安装应用列表
        $installedName = ['appstore'];
        $appList = Apps::appList();
        if (Base::isSuccess($appList)) {
            $installedName = array_merge(
                $installedName,
                array_column(
                    array_filter($appList['data'], fn($app) => $app['config']['status'] === 'installed'),
                    'name'
                )
            );
        }

        // 获取应用菜单
        $menusData = [];
        $res = Apps::getAppMenuItems();
        if (Base::isSuccess($res)) {
            $menusData = $res['data'];
        }

        return Base::retSuccess('success', [
            'installed' => $installedName,
            'menus' => $menusData,
        ]);
    }

    /**
     * @api {post} api/apps/install        05. 安装应用（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName install
     *
     * @apiParam {String} app_name         应用名称
     * @apiParam {String} [version]        版本号，不指定则使用最新版本
     * @apiParam {Object} [params]         应用参数
     * @apiParam {Object} [resources]      资源限制
     * @apiParam {String} [resources.cpu_limit]    CPU限制
     * @apiParam {String} [resources.memory_limit] 内存限制
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    安装结果信息
     */
    public function install()
    {
        $user = User::auth('admin');
        //
        $appName = Request::input('app_name');
        $version = Request::input('version', 'latest');
        $params = Request::input('params', []);
        $resources = Request::input('resources', []);

        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        // 获取应用配置
        $appConfig = Apps::getAppConfig($appName);

        // 保存用户设置的参数
        $updateConfig = [];

        // 记录安装用户
        $updateConfig['installer'] = is_array($appConfig['installer']) ? $appConfig['installer'] : [];
        $updateConfig['installer'][] = ['userid' => $user->userid, 'time' => Timer::time()];
        if (count($updateConfig['installer']) > 5) {
            $updateConfig['installer'] = array_slice($updateConfig['installer'], -5);
        }

        // 设置参数
        if (!empty($params) && is_array($params)) {
            $updateConfig['params'] = $params;
        }

        // 设置资源限制
        if (!empty($resources) && is_array($resources)) {
            $updateConfig['resources'] = $resources;
        }

        // 保存配置
        Apps::saveAppConfig($appName, $updateConfig);

        // 执行安装
        return Apps::dockerComposeUp($appName, $version);
    }

    /**
     * @api {get} api/apps/install/url        06. 通过url安装应用（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName install_url
     *
     * @apiParam {String} url         应用url
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    安装结果信息
     */
    public function install__url()
    {
        User::auth('admin');
        //
        $url = Request::input('url');

        // 下载应用
        $res = Apps::downloadApp($url);
        if (Base::isError($res)) {
            return $res;
        }

        // 安装应用
        return Apps::dockerComposeUp($res['app_name']);
    }

    /**
     * 更新应用状态（用于安装结束之后回调）
     *
     * @apiParam {String} app_name      应用名称
     * @apiParam {String} status       新状态，可选值: installed, error
     *
     * @return string
     */
    public function install__callback()
    {
        // 用户权限验证
        $authorization = Base::leftDelete(Request::header("Authorization"), "Bearer ");
        if ($authorization != md5(env('APP_KEY'))) {
            return 'Authorization error';
        }

        // 获取参数
        $appName = Request::input('app_name');
        $status = Request::input('status');

        if (empty($appName)) {
            return 'app name is empty';
        }

        // 处理状态
        $status = str_replace(['successful', 'failed'], ['installed', 'error'], $status);
        if (!in_array($status, ['installed', 'error'])) {
            return 'status is invalid';
        }

        // 最后一步处理
        $res = Apps::dockerComposeFinalize($appName, $status);
        if (Base::isError($res)) {
            return 'response error (' . $res['msg'] . ')';
        }
        return 'ok';
    }

    /**
     * @api {post} api/apps/uninstall      07. 卸载应用（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName uninstall
     *
     * @apiParam {String} app_name      应用名称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    卸载结果信息
     */
    public function uninstall()
    {
        $user = User::auth('admin');
        //
        $appName = Request::input('app_name');

        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        // 获取应用配置
        $appConfig = Apps::getAppConfig($appName);

        // 保存用户设置的参数
        $updateConfig = [];

        // 记录安装用户
        $updateConfig['uninstaller'] = is_array($appConfig['uninstaller']) ? $appConfig['uninstaller'] : [];
        $updateConfig['uninstaller'][] = ['userid' => $user->userid, 'time' => Timer::time()];
        if (count($updateConfig['uninstaller']) > 5) {
            $updateConfig['uninstaller'] = array_slice($updateConfig['uninstaller'], -5);
        }

        // 保存配置
        Apps::saveAppConfig($appName, $updateConfig);

        // 执行卸载
        return Apps::dockerComposeDown($appName);
    }

    /**
     * @api {get} api/apps/logs           08. 获取应用日志（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName logs
     *
     * @apiParam {String} app_name      应用名称
     * @apiParam {Number} [lines=50]    获取日志行数，默认50行
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String} data.name   应用名称
     * @apiSuccess {Object} data.config 应用配置信息
     * @apiSuccess {String} data.log    日志内容
     */
    public function logs()
    {
        User::auth('admin');
        //
        $appName = Request::input('app_name');
        $lines = intval(Request::input('lines', 50));

        $logContent = implode("\n", Apps::getAppLog($appName, $lines));

        return Base::retSuccess('success', [
            'name' => $appName,
            'config' => Apps::getAppConfig($appName),
            'log' => trim($logContent)
        ]);
    }
}
