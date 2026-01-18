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
}
