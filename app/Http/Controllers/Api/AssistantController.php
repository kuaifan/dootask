<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\AI;
use App\Module\Apps;
use App\Module\Base;
use Request;

/**
 * @apiDefine assistant
 *
 * 助手
 */
class AssistantController extends AbstractController
{
    public function __construct()
    {
        Apps::isInstalledThrow('ai');
    }

    /**
     * @api {post} api/assistant/auth 生成授权码
     *
     * @apiDescription 需要token身份，生成 AI 流式会话的 stream_key
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName auth
     *
     * @apiParam {String} model_type  模型类型
     * @apiParam {String} model_name  模型名称
     * @apiParam {JSON} context       上下文数组
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String} data.stream_key 流式会话凭证
     */
    public function auth()
    {
        $user = User::auth();
        $user->checkChatInformation();

        $modelType = trim(Request::input('model_type', ''));
        $modelName = trim(Request::input('model_name', ''));
        $contextInput = Request::input('context', []);

        return AI::createStreamKey($modelType, $modelName, $contextInput);
    }

    /**
     * @api {get} api/assistant/models 获取AI模型
     *
     * @apiDescription 获取所有AI机器人模型设置
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName models
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function models()
    {
        $setting = Base::setting('aibotSetting');
        $setting = array_filter($setting, function ($value, $key) {
            return str_ends_with($key, '_models') || str_ends_with($key, '_model');
        }, ARRAY_FILTER_USE_BOTH);

        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }
}
