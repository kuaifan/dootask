<?php

namespace App\Http\Controllers\Api;

use App\Models\AiAssistantSession;
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
