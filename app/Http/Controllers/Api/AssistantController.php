<?php

namespace App\Http\Controllers\Api;

use App\Models\AiAssistantFeedback;
use App\Models\AiAssistantSearchLog;
use App\Models\AiAssistantSession;
use App\Models\User;
use App\Models\WebSocket;
use App\Module\AI;
use App\Module\Apps;
use App\Module\Base;
use App\Tasks\PushTask;
use Cache;
use Illuminate\Support\Str;
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
     * @apiParam {String} [locale]    ai-kb 检索语种：zh、en（缺省取请求语言 language，包含 zh 视为 zh，否则 en）
     * @apiParam {String} [session_id] 前端会话ID（透传给 AI 服务作 context_key，用于检索打点关联）
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
        $locale = trim(Request::input('locale', '')) ?: trim(Base::headerOrInput('language'));
        $locale = str_contains(strtolower($locale), 'zh') ? 'zh' : 'en';
        $contextKey = mb_substr(trim(Request::input('session_id', '')), 0, 100);

        // 当前用户 WebSocket fd：供 AI 经 doo page 操作本人浏览器（页面操作用）。
        // 复用 operation__dispatch 同款归属校验：在表即在线、归属即本人，否则置 0。
        $fd = intval(Base::headerOrInput('fd'));
        if ($fd > 0 && intval(WebSocket::whereFd($fd)->value('userid')) !== intval($user->userid)) {
            $fd = 0;
        }

        // 灰度判定（参考 config/ai.php）：总开关 + canary 白名单
        $ragEnabled = AI::ragEnabledFor((int) $user->userid);

        return AI::createStreamKey($modelType, $modelName, $contextInput, $locale, $ragEnabled, $contextKey, $fd);
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

    /**
     * @api {post} api/assistant/match-elements 元素向量匹配
     *
     * @apiDescription 通过向量相似度匹配页面元素，用于智能查找与查询语义相关的元素
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName match_elements
     *
     * @apiParam {String} query     搜索关键词
     * @apiParam {Array} elements   元素列表，每个元素包含 ref 和 name 字段
     * @apiParam {Number} [top_k=10] 返回的匹配数量，最大50
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {Array} data.matches 匹配结果数组，按相似度降序排列
     */
    public function match_elements()
    {
        User::auth();

        $query = trim(Request::input('query', ''));
        $elements = Request::input('elements', []);
        $topK = min(intval(Request::input('top_k', 10)), 50);

        if (empty($query) || empty($elements)) {
            return Base::retError('参数不能为空');
        }

        // 获取查询向量
        $queryResult = AI::getEmbedding($query);
        if (Base::isError($queryResult)) {
            return $queryResult;
        }
        $queryVector = $queryResult['data'];

        // 计算相似度并排序
        $scored = [];
        foreach ($elements as $el) {
            $name = $el['name'] ?? '';
            if (empty($name)) {
                continue;
            }

            $elResult = AI::getEmbedding($name);
            if (Base::isError($elResult)) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryVector, $elResult['data']);
            $scored[] = [
                'element' => $el,
                'similarity' => $similarity,
            ];
        }

        // 按相似度降序排序
        usort($scored, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return Base::retSuccess('success', [
            'matches' => array_slice($scored, 0, $topK),
        ]);
    }

    /**
     * 计算两个向量的余弦相似度
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        $count = count($a);
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        $denominator = sqrt($normA) * sqrt($normB);
        if ($denominator == 0) {
            return 0;
        }
        return $dotProduct / $denominator;
    }

    /**
     * @api {post} api/assistant/log/search 记录帮助知识库检索日志
     *
     * @apiDescription 需要token身份（AI 插件透传用户 token 服务端回调）。记录一次 search_help_docs 检索，用于分析检索质量、反哺 ai-kb 内容迭代
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName log__search
     *
     * @apiParam {String} query          检索query
     * @apiParam {String} [locale]       语种 zh|en
     * @apiParam {String} [source]       来源 chat|invoke
     * @apiParam {String} [context_key]  上下文标识
     * @apiParam {Number} [dialog_id]    对话ID
     * @apiParam {Array}  [source_ids]   命中source id列表
     * @apiParam {Number} [top_score]    最高相似度
     * @apiParam {Number} [result_count] 命中数量
     * @apiParam {Number} [duration_ms]  检索耗时毫秒
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     */
    public function log__search()
    {
        $user = User::auth();

        $query = mb_substr(trim(Request::input('query', '')), 0, 500);
        $locale = trim(Request::input('locale', ''));
        $source = trim(Request::input('source', ''));
        $contextKey = mb_substr(trim(Request::input('context_key', '')), 0, 191);
        $dialogId = intval(Request::input('dialog_id', 0));
        $sourceIds = Request::input('source_ids', []);
        $topScore = floatval(Request::input('top_score', 0));
        $resultCount = intval(Request::input('result_count', 0));
        $durationMs = intval(Request::input('duration_ms', 0));

        if ($query === '') {
            return Base::retError('参数错误');
        }
        if (!in_array($source, ['chat', 'invoke'])) {
            $source = '';
        }
        if (!is_array($sourceIds)) {
            $sourceIds = [];
        }

        $log = AiAssistantSearchLog::createInstance([
            'userid' => $user->userid,
            'dialog_id' => max(0, $dialogId),
            'context_key' => $contextKey,
            'source' => $source,
            'query' => $query,
            'locale' => in_array($locale, ['zh', 'en']) ? $locale : '',
            'source_ids' => Base::array2json(array_slice(array_values($sourceIds), 0, 10)),
            'top_score' => max(0, min(1, $topScore)),
            'result_count' => max(0, $resultCount),
            'duration_ms' => max(0, $durationMs),
            'empty' => $resultCount > 0 ? 0 : 1,
        ]);
        $log->save();

        return Base::retSuccess('success');
    }

    /**
     * @api {post} api/assistant/feedback/save 保存回复反馈
     *
     * @apiDescription 需要token身份。保存用户对一条 AI 回复的 👍/👎 反馈，同一条回复可改票（覆盖更新）；传空 feedback 表示取消反馈（删除记录）
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName feedback__save
     *
     * @apiParam {String} session_key   场景分类key
     * @apiParam {String} session_id    前端会话ID
     * @apiParam {Number} local_id      回复条目localId
     * @apiParam {String} feedback      like|dislike，空字符串表示取消反馈
     * @apiParam {String} [prompt]      用户问题
     * @apiParam {String} [answer]      回复摘录
     * @apiParam {Array}  [source_ids]  回复引用的kb source id列表
     * @apiParam {String} [model]       模型名
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String} data.feedback 已保存的反馈值
     */
    public function feedback__save()
    {
        $user = User::auth();

        $sessionKey = mb_substr(trim(Request::input('session_key', 'default')), 0, 100);
        $sessionId = mb_substr(trim(Request::input('session_id', '')), 0, 100);
        $localId = intval(Request::input('local_id', 0));
        $feedback = trim(Request::input('feedback', ''));
        $prompt = mb_substr(trim(Request::input('prompt', '')), 0, 1000);
        $answer = mb_substr(trim(Request::input('answer', '')), 0, 2000);
        $sourceIds = Request::input('source_ids', []);
        $model = mb_substr(trim(Request::input('model', '')), 0, 100);

        if (empty($sessionId) || $localId <= 0) {
            return Base::retError('参数错误');
        }
        if (!in_array($feedback, ['', 'like', 'dislike'])) {
            return Base::retError('反馈类型错误');
        }
        if (!is_array($sourceIds)) {
            $sourceIds = [];
        }

        $exist = AiAssistantFeedback::where('userid', $user->userid)
            ->where('session_key', $sessionKey)
            ->where('session_id', $sessionId)
            ->where('local_id', $localId)
            ->first();

        // 空反馈表示取消：删除已有记录
        if ($feedback === '') {
            $exist?->delete();
            return Base::retSuccess('success', [
                'feedback' => '',
            ]);
        }

        $row = AiAssistantFeedback::createInstance([
            'userid' => $user->userid,
            'session_key' => $sessionKey,
            'session_id' => $sessionId,
            'local_id' => $localId,
            'feedback' => $feedback,
            'prompt' => $prompt,
            'answer' => $answer,
            'answer_digest' => md5($answer),
            'source_ids' => Base::array2json(array_slice(array_values($sourceIds), 0, 10)),
            'model' => $model,
        ], $exist?->id);
        $row->save();

        return Base::retSuccess('success', [
            'feedback' => $feedback,
        ]);
    }

    /**
     * @api {post} api/assistant/operation/dispatch 派发页面操作
     *
     * @apiDescription 需要token身份。通过用户常驻 WebSocket（/ws）向其浏览器派发一次页面操作（获取页面上下文 / 执行动作 / 操作元素），由前端 AI 助手执行后经 operationResult 回传，结果写入缓存供 operation/result 轮询取走。复用主程序 /ws，无需为页面操作另开 WebSocket。
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName operation__dispatch
     *
     * @apiParam {Number} fd        目标会话 fd（须为当前用户在线的 WebSocket 连接）
     * @apiParam {String} action    操作类型，如 get_page_context|execute_action|execute_element_action
     * @apiParam {Object} [payload] 操作参数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String} data.requestId 本次操作的请求ID，用于轮询 operation/result
     */
    public function operation__dispatch()
    {
        $user = User::auth();

        $fd = intval(Base::headerOrInput('fd'));
        $action = trim(Request::input('action', ''));
        $payload = Request::input('payload', []);

        if ($fd <= 0 || $action === '') {
            return Base::retError('参数错误');
        }
        if (!is_array($payload)) {
            $payload = [];
        }

        // fd 归属校验：在表即在线，归属即本人
        $ownerId = WebSocket::whereFd($fd)->value('userid');
        if (intval($ownerId) !== intval($user->userid)) {
            return Base::retError('会话不存在或无权限');
        }

        $requestId = Str::random(24);

        // 精确推送到该 fd，不补发离线消息
        PushTask::push([
            'fd' => $fd,
            'msg' => [
                'type' => 'operation',
                'data' => [
                    'requestId' => $requestId,
                    'action' => $action,
                    'payload' => $payload,
                ],
            ],
        ], false);

        return Base::retSuccess('success', [
            'requestId' => $requestId,
        ]);
    }

    /**
     * @api {get} api/assistant/operation/result 取页面操作结果
     *
     * @apiDescription 需要token身份。轮询取走 operation/dispatch 派发的一次页面操作结果（取走即删）；未回传时返回 status=pending。
     * @apiVersion 1.0.0
     * @apiGroup assistant
     * @apiName operation__result
     *
     * @apiParam {String} request_id 操作请求ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String} data.status ready|pending
     */
    public function operation__result()
    {
        $user = User::auth();

        $requestId = trim(Base::headerOrInput('request_id'));
        if ($requestId === '') {
            return Base::retError('参数错误');
        }

        $row = Cache::get("ai_op_result:{$requestId}");
        if (!is_array($row)) {
            return Base::retSuccess('success', ['status' => 'pending']);
        }
        // 命中后校验归属再取走，避免越权读取他人结果
        if (intval($row['userid']) !== intval($user->userid)) {
            return Base::retError('无权限');
        }
        Cache::forget("ai_op_result:{$requestId}");

        return Base::retSuccess('success', [
            'status' => 'ready',
            'success' => !empty($row['success']),
            'result' => $row['result'] ?? null,
            'error' => $row['error'] ?? null,
        ]);
    }

    /**
     * 获取会话列表
     */
    public function session__list()
    {
        $user = User::auth();
        $sessionKey = trim(Request::input('session_key', 'default'));

        $sessions = AiAssistantSession::where('userid', $user->userid)
            ->where('session_key', $sessionKey)
            ->orderByDesc('updated_at')
            ->get();

        $list = [];
        foreach ($sessions as $session) {
            $data = Base::json2array($session->data);
            $images = Base::json2array($session->images);
            foreach ($images as $imageId => $path) {
                $images[$imageId] = Base::fillUrl($path);
            }
            $list[] = [
                'id' => $session->session_id,
                'title' => $session->title,
                'responses' => $data,
                'images' => $images,
                'sceneKey' => $session->scene_key,
                'createdAt' => $session->created_at ? $session->created_at->getTimestampMs() : 0,
                'updatedAt' => $session->updated_at ? $session->updated_at->getTimestampMs() : 0,
            ];
        }

        return Base::retSuccess('success', $list);
    }

    /**
     * 保存会话
     */
    public function session__save()
    {
        $user = User::auth();
        $sessionKey = trim(Request::input('session_key', 'default'));
        $sessionId = trim(Request::input('session_id', ''));
        $sceneKey = trim(Request::input('scene_key', ''));
        $title = trim(Request::input('title', ''));
        $data = Request::input('data', []);
        $newImages = Request::input('new_images', []);

        if (empty($sessionId)) {
            return Base::retError('session_id 不能为空');
        }

        $newImageUrls = [];
        if (is_array($newImages)) {
            $path = 'uploads/assistant/' . date('Ym') . '/' . $user->userid . '/';
            foreach ($newImages as $img) {
                $imageId = $img['imageId'] ?? '';
                $dataUrl = $img['dataUrl'] ?? '';
                if (empty($imageId) || empty($dataUrl)) {
                    continue;
                }
                $result = Base::image64save([
                    'image64' => $dataUrl,
                    'path' => $path,
                    'autoThumb' => false,
                ]);
                if (Base::isSuccess($result)) {
                    $newImageUrls[$imageId] = $result['data']['path'];
                }
            }
        }

        $session = AiAssistantSession::where('userid', $user->userid)
            ->where('session_key', $sessionKey)
            ->where('session_id', $sessionId)
            ->first();

        $imageMap = $newImageUrls;
        if ($session) {
            $existingImages = Base::json2array($session->images);
            $imageMap = array_merge($existingImages, $newImageUrls);
        }

        $session = AiAssistantSession::createInstance([
            'userid' => $user->userid,
            'session_key' => $sessionKey,
            'session_id' => $sessionId,
            'scene_key' => $sceneKey,
            'title' => mb_substr($title, 0, 255),
            'data' => Base::array2json(is_array($data) ? $data : []),
            'images' => Base::array2json($imageMap),
        ], $session?->id);
        $session->save();

        // 仅返回本次新增的图片URL
        $urls = [];
        foreach ($newImageUrls as $imageId => $path) {
            $urls[$imageId] = Base::fillUrl($path);
        }

        return Base::retSuccess('success', [
            'image_urls' => $urls,
        ]);
    }

    /**
     * 删除会话
     */
    public function session__delete()
    {
        $user = User::auth();
        $sessionKey = trim(Request::input('session_key', 'default'));
        $sessionId = trim(Request::input('session_id', ''));
        $clearAll = Request::input('clear_all', false);

        $query = AiAssistantSession::where('userid', $user->userid)
            ->where('session_key', $sessionKey);

        if ($clearAll) {
            $sessions = $query->get();
            foreach ($sessions as $session) {
                $this->deleteSessionImages($session);
            }
            $query->delete();
        } else {
            if (empty($sessionId)) {
                return Base::retError('session_id 不能为空');
            }
            $session = $query->where('session_id', $sessionId)->first();
            if ($session) {
                $this->deleteSessionImages($session);
                $session->delete();
            }
        }

        return Base::retSuccess('success');
    }

    private function deleteSessionImages(AiAssistantSession $session)
    {
        $images = Base::json2array($session->images);
        foreach ($images as $path) {
            $fullPath = public_path($path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}
