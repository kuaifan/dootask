<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use Request;

/**
 * @apiDefine assistant
 *
 * 助手
 */
class AssistantController extends AbstractController
{
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

        if ($modelType === '' || $modelName === '') {
            return Base::retError('参数错误');
        }

        if (is_string($contextInput)) {
            $decoded = json_decode($contextInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $contextInput = $decoded;
            }
        }
        if (!is_array($contextInput)) {
            return Base::retError('context 参数格式错误');
        }

        $context = [];
        foreach ($contextInput as $item) {
            if (!is_array($item) || count($item) < 2) {
                continue;
            }
            $role = trim((string)($item[0] ?? ''));
            $message = trim((string)($item[1] ?? ''));
            if ($role === '' || $message === '') {
                continue;
            }
            $context[] = [$role, $message];
        }

        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        if ($contextJson === false) {
            return Base::retError('context 参数格式错误');
        }

        $setting = Base::setting('aibotSetting');
        $apiKey = Base::val($setting, $modelType . '_key');
        if ($modelType === 'wenxin') {
            $wenxinSecret = Base::val($setting, 'wenxin_secret');
            if ($wenxinSecret) {
                $apiKey = trim(($apiKey ?: '') . ':' . $wenxinSecret);
            }
        }
        if ($modelType === 'ollama' && empty($apiKey)) {
            $apiKey = Base::strRandom(6);
        }
        if (empty($apiKey)) {
            return Base::retError('模型未启用');
        }

        $remoteModelType = match ($modelType) {
            'qianwen' => 'qwen',
            default => $modelType,
        };

        $authParams = [
            'api_key' => $apiKey,
            'model_type' => $remoteModelType,
            'model_name' => $modelName,
            'context' => $contextJson,
        ];

        if ($setting[$modelType . '_base_url']) {
            $authParams['base_url'] = $setting[$modelType . '_base_url'];
        }
        if ($setting[$modelType . '_agency']) {
            $authParams['agency'] = $setting[$modelType . '_agency'];
        }

        $thinkPatterns = [
            "/^(.+?)(\s+|\s*[_-]\s*)(think|thinking|reasoning)\s*$/",
            "/^(.+?)\s*\(\s*(think|thinking|reasoning)\s*\)\s*$/"
        ];
        $thinkMatch = [];
        foreach ($thinkPatterns as $pattern) {
            if (preg_match($pattern, $authParams['model_name'], $thinkMatch)) {
                break;
            }
        }
        if ($thinkMatch && !empty($thinkMatch[1])) {
            $authParams['model_name'] = $thinkMatch[1];
        }

        $authResult = Ihttp::ihttp_post('http://nginx/ai/invoke/auth', $authParams, 30);

        if (Base::isError($authResult)) {
            return Base::retError($authResult['msg']);
        }

        $body = Base::json2array($authResult['data']);
        if ($body['code'] !== 200) {
            return Base::retError($body['error'] ?: 'AI 接口返回异常', $body);
        }

        $streamKey = Base::val($body, 'data.stream_key');
        if (empty($streamKey)) {
            return Base::retError('AI 接口返回数据异常');
        }

        return Base::retSuccess('success', [
            'stream_key' => $streamKey,
        ]);
    }
}
