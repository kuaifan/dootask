<?php

namespace App\Http\Controllers\Api;

use App\Module\Apps\Apps;
use App\Module\Base;
use Request;

/**
 * @apiDefine apps
 *
 * 应用相关接口
 */
class AppsController extends AbstractController
{
    /**
     * @api {get} api/apps/list           01. 获取应用列表
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
        return Apps::appList();
    }

    /**
     * @api {get} api/apps/info           02. 获取应用详情
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
     * @apiSuccess {Object} data.info    应用基本信息
     * @apiSuccess {Object} data.local   应用本地安装信息
     * @apiSuccess {Array}  data.versions 可用版本列表
     */
    public function info()
    {
        $appName = Request::input('app_name');
        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        return Apps::appInfo($appName);
    }

    /**
     * @api {post} api/apps/install        03. 安装应用
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
        $appName = Request::input('app_name');
        $version = Request::input('version', 'latest');
        $params = Request::input('params', []);
        $resources = Request::input('resources', []);

        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        // 保存用户设置的参数
        $localData = [];

        // 设置参数
        if (!empty($params) && is_array($params)) {
            $localData['params'] = $params;
        }

        // 设置资源限制
        if (!empty($resources) && is_array($resources)) {
            $localData['resources'] = $resources;
        }

        // 保存配置
        if (!empty($localData)) {
            Apps::saveAppLocalInfo($appName, $localData);
        }

        // 执行安装
        return Apps::dockerComposeUp($appName, $version);
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
     * @api {post} api/apps/uninstall      04. 卸载应用
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
        $appName = Request::input('app_name');

        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        // 执行卸载
        return Apps::dockerComposeDown($appName);
    }

    /**
     * @api {get} api/apps/logs           05. 获取应用日志
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
     * @apiSuccess {String} data.log    日志内容
     */
    public function logs()
    {
        $appName = Request::input('app_name');
        $lines = intval(Request::input('lines', 50));

        if (empty($appName)) {
            return Base::retError('应用名称不能为空');
        }

        // 限制获取行数
        if ($lines <= 0) {
            $lines = 50;
        } else if ($lines > 2000) {
            $lines = 2000;
        }

        // 日志文件路径
        $logFile = base_path('docker/logs/apps/' . $appName . '.log');

        if (!file_exists($logFile)) {
            return Base::retSuccess('日志返回成功', [
                'log' => ''
            ]);
        }

        // 读取日志文件最后几行
        $output = [];
        $cmd = 'tail -n ' . $lines . ' ' . escapeshellarg($logFile);
        exec($cmd, $output);
        $logContent = implode("\n", $output);

        return Base::retSuccess('日志返回成功', [
            'log' => $logContent
        ]);
    }
}
