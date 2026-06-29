<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Badge;
use App\Module\Base;
use Request;

/**
 * 插件 / 微应用相关接口。
 *
 * 动态路由（routes/web.php）：
 *   api/apps/badge/set     -> badge__set()    应用密钥鉴权，绝对设置/清除角标
 *   api/apps/badge/clear   -> badge__clear()  当前用户 token 鉴权，清除自己的角标
 *   api/apps/badge/list    -> badge__list()   当前用户 token 鉴权，拉取自己全部角标（初始同步）
 */
class AppsController extends AbstractController
{
    /**
     * @api {post} api/apps/badge/set 设置角标（应用密钥鉴权）
     *
     * @apiDescription 由插件服务端使用 APP_SECRET 调用，对 (appid, 菜单, 每个 userid) 绝对设置角标（幂等覆盖）。
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName badge__set
     *
     * @apiParam {String} appid     应用ID
     * @apiParam {String} secret    应用密钥（APP_SECRET）
     * @apiParam {Number|Number[]} userid   目标用户ID（单个或数组）
     * @apiParam {String} [menu_key]    菜单稳定标识；留空表示该应用第一个菜单
     * @apiParam {Number} [count=0]     角标数字
     * @apiParam {Boolean} [dot=false]  是否显示红点（count=0 且 dot=false 即清除）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息
     * @apiSuccess {Object} data    返回数据
     */
    public function badge__set()
    {
        return Base::retSuccess('success', Badge::set(
            trim(Request::input('appid', '')),
            trim(Request::input('secret', '')),
            Request::input('userid'),
            trim(Request::input('menu_key', '')),
            Request::input('count', 0),
            Request::input('dot', false)
        ));
    }

    /**
     * @api {post} api/apps/badge/clear 清除角标（当前用户 token 鉴权）
     *
     * @apiDescription 供前端在 badge_clear_on_open=true 的菜单打开时调用，清除当前用户在该应用该菜单的角标。
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName badge__clear
     *
     * @apiParam {String} appid         应用ID
     * @apiParam {String} [menu_key]    菜单稳定标识；留空表示该应用第一个菜单
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息
     * @apiSuccess {Object} data    返回数据
     */
    public function badge__clear()
    {
        $user = User::auth();
        return Base::retSuccess('success', Badge::clearForUser(
            (int)$user->userid,
            trim(Request::input('appid', '')),
            trim(Request::input('menu_key', ''))
        ));
    }

    /**
     * @api {get} api/apps/badge/list 拉取自己全部角标
     *
     * @apiDescription 供前端初始同步：返回当前用户全部应用（插件 + 自定义微应用）的角标快照。
     *  数据结构 app_id => menu_key => {count, dot}，与前端 store 的 map 结构一致。
     * @apiVersion 1.0.0
     * @apiGroup apps
     * @apiName badge__list
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息
     * @apiSuccess {Object} data    返回数据
     */
    public function badge__list()
    {
        $user = User::auth();
        return Base::retSuccess('success', Badge::userBadges((int)$user->userid));
    }
}
