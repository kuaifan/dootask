<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\AI;
use App\Module\Base;
use App\Module\Ihttp;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use Request;

/**
 * @apiDefine assistant
 *
 * 助手
 */
class AssistantController extends AbstractController
{
    /**
     * @api {post} api/assistant/dialog/prompt 生成对话提示词
     *
     * @apiDescription 需要token身份，整理当前会话的系统提示词与上下文信息
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName dialog__prompt
     *
     * @apiParam {Number} dialog_id             对话ID
     * @apiParam {String} [content]             消息需求描述（用于提示词整理，可为空）
     * @apiParam {String} [draft]               当前草稿内容（HTML 格式）
     * @apiParam {Number} [quote_id]            引用消息ID
     *
     * @apiSuccess {Number} ret                 返回状态码（1正确、0错误）
     * @apiSuccess {String} msg                 返回信息（错误描述）
     * @apiSuccess {Object} data                返回数据
     * @apiSuccess {String} data.system_prompt  AI 使用的系统提示词
     * @apiSuccess {String} data.context_prompt AI 使用的上下文提示词
     */
    public function dialog__prompt()
    {
        $user = User::auth();
        $user->checkChatInformation();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $content = trim(Request::input('content', ''));
        if ($dialog_id <= 0) {
            return Base::retError('参数错误');
        }

        $dialog = WebSocketDialog::checkDialog($dialog_id);

        // 基本信息
        $context = [
            'dialog_name' => $dialog->name ?: '',
            'dialog_type' => $dialog->type ?: '',
            'group_type' => $dialog->group_type ?: '',
        ];

        // 当前草稿
        $draft = Request::input('draft', '');
        if (is_string($draft) && trim($draft) !== '') {
            $context['current_draft'] = Base::html2markdown($draft);
        }

        // 引用消息
        $quote_id = intval(Request::input('quote_id'));
        if ($quote_id > 0) {
            $quote = WebSocketDialogMsg::whereDialogId($dialog_id)
                ->whereId($quote_id)
                ->with('user')
                ->first();
            if ($quote) {
                $context['quote_summary'] = WebSocketDialogMsg::previewMsg($quote);
                $context['quote_user'] = $quote->user->nickname ?? '';
            }
        }

        // 成员列表
        $members = WebSocketDialogUser::whereDialogId($dialog_id)
            ->join('users', 'users.userid', '=', 'web_socket_dialog_users.userid')
            ->orderBy('web_socket_dialog_users.id')
            ->limit(10)
            ->pluck('users.nickname')
            ->filter()
            ->values()
            ->all();
        if (!empty($members)) {
            $context['members'] = $members;
        }

        // 最近消息
        $recentMessagesQuery = WebSocketDialogMsg::whereDialogId($dialog_id)
            ->orderByDesc('id')
            ->with('user');

        $recentMessages = (clone $recentMessagesQuery)->take(15)->get();
        if ($recentMessages->isNotEmpty()) {
            $formatRecentMessages = function ($messages) {
                return $messages->reverse()->map(function ($msg) {
                    return [
                        'sender' => $msg->user->nickname ?? ('用户' . $msg->userid),
                        'summary' => $msg->extractMessageContent(500),
                    ];
                })->filter(function ($item) {
                    return !empty($item['summary']);
                })->values();
            };

            $formattedRecentMessages = $formatRecentMessages($recentMessages);
            $summaryLength = $formattedRecentMessages->sum(function ($item) {
                return mb_strlen($item['summary']);
            });

            if ($summaryLength < 500 && $recentMessages->count() === 15) {
                $lastMessageId = optional($recentMessages->last())->id;
                $additionalMessages = collect();
                if ($lastMessageId) {
                    $additionalMessages = (clone $recentMessagesQuery)
                        ->where('id', '<', $lastMessageId)
                        ->take(10)
                        ->get();
                }
                if ($additionalMessages->isNotEmpty()) {
                    $recentMessages = $recentMessages->concat($additionalMessages);
                    $formattedRecentMessages = $formatRecentMessages($recentMessages);
                }
            }

            if ($formattedRecentMessages->isNotEmpty()) {
                $context['recent_messages'] = $formattedRecentMessages->all();
            }
        }

        // 生成消息
        $systemPrompt = AI::messageSystemPrompt();
        $contextPrompt = AI::buildMessageContextPrompt($context);
        if ($content) {
            $contextPrompt .= "\n\n请根据以上信息，结合提示词生成一条待发送的消息：";
        }

        return Base::retSuccess('success', [
            'system_prompt' => $systemPrompt,
            'context_prompt' => $contextPrompt,
        ]);
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

        $authResult = Ihttp::ihttp_post('http://nginx/ai/invoke/auth', [
            'api_key' => $apiKey,
            'model_type' => $remoteModelType,
            'model_name' => $modelName,
            'context' => $contextJson,
        ], 30);

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
