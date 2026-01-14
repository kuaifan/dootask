<?php

namespace App\Module;

use App\Models\Setting;
use App\Models\User;
use Cache;
use Carbon\Carbon;

/**
 * AI 助手模块
 */
class AI
{
    public const TEXT_MODEL_PRIORITY = [
        'openai',
        'claude',
        'deepseek',
        'gemini',
        'grok',
        'ollama',
        'zhipu',
        'qianwen',
        'wenxin'
    ];
    protected const OPENAI_DEFAULT_MODEL = 'gpt-5.1-mini';

    protected $post = [];
    protected $headers = [];
    protected $urlPath = '';
    protected $timeout = 30;
    protected $providerConfig = null;

    /**
     * 构造函数
     * @param array $post
     * @param array $headers
     */
    public function __construct($post = [], $headers = [])
    {
        $this->post = $post ?? [];
        $this->headers = $headers ?? [];
    }

    /**
     * 设置请求参数
     * @param array $post
     */
    public function setPost($post)
    {
        $this->post = array_merge($this->post, $post);
    }

    /**
     * 设置请求头
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * 设置请求路径
     * @param string $urlPath
     */
    public function setUrlPath($urlPath)
    {
        $this->urlPath = $urlPath;
    }

    /**
     * 设置请求超时时间
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * 指定请求所使用的模型配置
     * @param array $provider
     */
    public function setProvider(array $provider)
    {
        $this->providerConfig = $provider;
    }

    /**
     * 请求 AI 接口
     * @param bool $resRaw 是否返回原始数据
     * @return array
     */
    public function request($resRaw = false)
    {
        $provider = $this->providerConfig ?: self::resolveTextProvider();
        if (!$provider) {
            return Base::retError("请先配置 AI 助手");
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $provider['api_key'],
        ];
        if (!empty($provider['agency'])) {
            $headers['CURLOPT_PROXY'] = $provider['agency'];
            $headers['CURLOPT_PROXYTYPE'] = str_contains($provider['agency'], 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }
        $headers = array_merge($headers, $this->headers);

        $baseUrl = $provider['base_url'] ?: 'https://api.openai.com/v1';
        $url = $baseUrl . ($this->urlPath ?: '/chat/completions');

        $result = Ihttp::ihttp_request($url, $this->post, $headers, $this->timeout);
        if (Base::isError($result)) {
            return Base::retError("AI 接口请求失败", $result);
        }
        $result = $result['data'];

        if (!$resRaw) {
            $resData = Base::json2array($result);
            if (empty($resData['choices'])) {
                return Base::retError("AI 接口返回数据格式错误", $resData);
            }
            $result = $resData['choices'][0]['message']['content'];
            $result = trim($result);
            if (empty($result)) {
                return Base::retError("AI 接口返回数据为空");
            }
        }

        return Base::retSuccess("success", $result);
    }

    /**
     * 生成 AI 流式会话凭证
     * @param string $modelType
     * @param string $modelName
     * @param mixed $contextInput
     * @return array
     */
    public static function createStreamKey($modelType, $modelName, $contextInput = [])
    {
        $modelType = trim((string)$modelType);
        $modelName = trim((string)$modelName);

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
            // 替换系统条件性提示块占位符
            if (str_contains($message, '{{SYSTEM_OPTIONAL_PROMPTS}}')) {
                $optionalPrompts = PromptPlaceholder::buildOptionalPrompts(User::userid());
                $message = str_replace('{{SYSTEM_OPTIONAL_PROMPTS}}', $optionalPrompts, $message);
            }
            $context[] = [$role, $message];
        }

        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        if ($contextJson === false) {
            return Base::retError('context 参数格式错误');
        }

        $setting = Base::setting('aibotSetting');
        if (!is_array($setting)) {
            $setting = [];
        }

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

        $baseUrl = trim((string)($setting[$modelType . '_base_url'] ?? ''));
        if ($baseUrl !== '') {
            $authParams['base_url'] = $baseUrl;
        }

        $agency = trim((string)($setting[$modelType . '_agency'] ?? ''));
        if ($agency !== '') {
            $authParams['agency'] = $agency;
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

        $authResult = Ihttp::ihttp_request('http://nginx/ai/invoke/auth', $authParams, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . Base::token(),
        ], 30);
        if (Base::isError($authResult)) {
            return Base::retError($authResult['msg']);
        }

        $body = Base::json2array($authResult['data']);
        if (($body['code'] ?? null) !== 200) {
            return Base::retError(($body['error'] ?? '') ?: 'AI 接口返回异常', $body);
        }

        $streamKey = Base::val($body, 'data.stream_key');
        if (empty($streamKey)) {
            return Base::retError('AI 接口返回数据异常');
        }

        return Base::retSuccess('success', [
            'stream_key' => $streamKey,
        ]);
    }

    /** ******************************************************************************************** */
    /** ******************************************************************************************** */
    /** ******************************************************************************************** */

    /**
     * 通过 openAI 语音转文字
     * @param string $filePath 语音文件路径
     * @param array $extParams 扩展参数
     * @param array $extHeaders 扩展请求头
     * @param bool $noCache 是否禁用缓存
     * @return array
     */
    public static function transcriptions($filePath, $extParams = [], $extHeaders = [], $noCache = false)
    {
        Apps::isInstalledThrow('ai');

        $extParams = $extParams ?: [];
        $extHeaders = $extHeaders ?: [];

        if (!file_exists($filePath)) {
            return Base::retError("语音文件不存在");
        }
        $cacheKey = "openAItranscriptions::" . md5($filePath . '_' . Base::array2json($extParams));
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $audioProvider = self::resolveOpenAIAudioProvider();
        if (!$audioProvider) {
            return Base::retError("请先在「AI 助手」设置中配置 OpenAI");
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addDays(), function () use ($extParams, $extHeaders, $filePath, $audioProvider) {
            $post = array_merge($extParams, [
                'file' => new \CURLFile($filePath),
                'model' => 'gpt-4o-mini-transcribe',
            ]);
            $header = array_merge($extHeaders, [
                'Content-Type' => 'multipart/form-data',
            ]);

            $ai = new self($post, $header);
            $ai->setProvider($audioProvider);
            $ai->setUrlPath('/audio/transcriptions');
            $ai->setTimeout(15);

            $res = $ai->request(true);
            if (Base::isError($res)) {
                return Base::retError("语音转文字失败", $res);
            }
            $resData = Base::json2array($res['data']);
            if (empty($resData['text'])) {
                return Base::retError("语音转文字失败", $resData);
            }

            return Base::retSuccess("success", [
                'file' => $filePath,
                'text' => $resData['text'],
            ]);
        });
        if (Base::isError($result)) {
            Cache::forget($cacheKey);
        }
        return $result;
    }

    /**
     * 通过 openAI 翻译
     * @param string $text 需要翻译的文本内容
     * @param string $targetLanguage 目标语言（如：English, 简体中文, 日本語等）
     * @param bool $noCache 是否禁用缓存
     * @return array
     */
    public static function translations($text, $targetLanguage, $noCache = false)
    {
        Apps::isInstalledThrow('ai');

        $cacheKey = "openAItranslations::" . md5($text . '_' . $targetLanguage);
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $provider = self::resolveTextProvider();
        if (!$provider) {
            return Base::retError("请先配置 AI 助手");
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addDays(7), function () use ($text, $targetLanguage, $provider) {
            $payload = [
                "model" => $provider['model'],
                "messages" => [
                    [
                        "role" => "system",
                        "content" => <<<EOF
                            你是一名资深的专业翻译专家，专门从事项目任务管理系统的多语言本地化工作。

                            翻译任务：将提供的文本内容翻译为 {$targetLanguage}

                            专业要求：
                            1. 术语一致性：确保项目管理、任务管理、团队协作等专业术语的准确翻译
                            2. 上下文理解：根据项目管理场景选择最合适的表达方式
                            3. 格式保持：严格保持原文的格式、结构、标点符号和排版
                            4. 语言规范：使用目标语言的标准表达，符合该语言的语法和习惯
                            5. 专业性：体现项目管理领域的专业水准和准确性
                            6. 简洁性：避免冗余表达，保持语言简洁明了

                            注意事项：
                            - 保留所有HTML标签、特殊符号、数字、日期格式
                            - 对于专有名词（如软件名称、品牌名）保持原文
                            - 确保翻译后的文本自然流畅，符合目标语言的表达习惯
                            - 如遇到歧义表达，优先选择项目管理场景下的含义

                            请直接返回翻译结果，不要包含任何解释或标记。
                            EOF
                    ],
                    [
                        "role" => "user",
                        "content" => "请将以下内容翻译为 {$targetLanguage}：\n\n{$text}"
                    ]
                ],
            ];
            $reasoningEffort = self::getReasoningEffort($provider);
            if ($reasoningEffort !== null) {
                $payload['reasoning_effort'] = $reasoningEffort;
            }
            $post = json_encode($payload);

            $ai = new self($post);
            $ai->setProvider($provider);
            $ai->setTimeout(60);

            $res = $ai->request();
            if (Base::isError($res)) {
                return Base::retError("翻译请求失败", $res);
            }
            $result = $res['data'];
            if (empty($result)) {
                return Base::retError("翻译结果为空");
            }

            return Base::retSuccess("success", [
                'translated_text' => $result,
                'target_language' => $targetLanguage,
                'translated_at' => date('Y-m-d H:i:s')
            ]);
        });

        if (Base::isError($result)) {
            Cache::forget($cacheKey);
        }
        return $result;
    }

    /**
     * 通过 openAI 生成标题
     * @param string $text 需要生成标题的文本内容
     * @param bool $noCache 是否禁用缓存
     * @return array
     */
    public static function generateTitle($text, $noCache = false)
    {
        if (!Apps::isInstalled('ai')) {
            return Base::retError('应用「AI Assistant」未安装');
        }

        $cacheKey = "openAIGenerateTitle::" . md5($text);
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $provider = self::resolveTextProvider();
        if (!$provider) {
            return Base::retError("请先配置 AI 助手");
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addHours(24), function () use ($text, $provider) {
            $payload = [
                "model" => $provider['model'],
                "messages" => [
                    [
                        "role" => "system",
                        "content" => <<<EOF
                            你是一个专业的标题生成器，专门为项目任务管理系统的对话内容生成精准、简洁的标题。

                            要求：
                            1. 标题要准确概括文本的核心内容和主要意图
                            2. 标题长度控制在5-20个字符之间
                            3. 语言简洁明了，避免冗余词汇
                            4. 适合在项目管理场景中使用
                            5. 不要包含引号或特殊符号
                            6. 如果是技术讨论，突出技术要点
                            7. 如果是项目管理内容，突出关键动作或目标
                            8. 如果是需求讨论，突出需求的核心点

                            请直接返回标题，不要包含任何解释或其他内容。
                            EOF
                    ],
                    [
                        "role" => "user",
                        "content" => "请为以下内容生成一个合适的标题：\n\n" . $text
                    ]
                ],
            ];
            $reasoningEffort = self::getReasoningEffort($provider);
            if ($reasoningEffort !== null) {
                $payload['reasoning_effort'] = $reasoningEffort;
            }
            $post = json_encode($payload);

            $ai = new self($post);
            $ai->setProvider($provider);
            $ai->setTimeout(10);

            $res = $ai->request();
            if (Base::isError($res)) {
                return Base::retError("标题生成失败", $res);
            }
            $result = $res['data'];
            if (empty($result)) {
                return Base::retError("生成的标题为空");
            }

            return Base::retSuccess("success", [
                'title' => $result,
                'length' => mb_strlen($result),
                'generated_at' => date('Y-m-d H:i:s')
            ]);
        });

        if (Base::isError($result)) {
            Cache::forget($cacheKey);
        }

        return $result;
    }

    /**
     * 通过 openAI 生成职场笑话、心灵鸡汤
     * @param bool $noCache 是否禁用缓存
     * @return array 返回20个笑话和20个心灵鸡汤
     */
    public static function generateJokeAndSoup($noCache = false)
    {
        if (!Apps::isInstalled('ai')) {
            return Base::retError('应用「AI Assistant」未安装');
        }

        $cacheKey = "openAIJokeAndSoup::" . md5(date('Y-m-d'));
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $provider = self::resolveTextProvider();
        if (!$provider) {
            return Base::retError("请先配置 AI 助手");
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addHours(6), function () use ($provider) {
            $payload = [
                "model" => $provider['model'],
                "messages" => [
                    [
                        "role" => "system",
                        "content" => <<<EOF
                            你是一个专业的内容生成器。

                            要求：
                            1. 笑话要幽默风趣，适合职场环境，内容积极正面
                            2. 心灵鸡汤要励志温暖，适合职场人士阅读
                            3. 每个笑话和鸡汤都要简洁明了，尽量不超过100字
                            4. 必须严格按照以下JSON格式返回，不要markdown格式，不要包含任何其他内容：

                            {
                                "jokes": [
                                    "笑话内容1",
                                    "笑话内容2",
                                    ...
                                ],
                                "soups": [
                                    "心灵鸡汤内容1",
                                    "心灵鸡汤内容2",
                                    ...
                                ]
                            }
                            EOF
                    ],
                    [
                        "role" => "user",
                        "content" => "请生成20个职场笑话和20个心灵鸡汤"
                    ]
                ],
            ];
            $reasoningEffort = self::getReasoningEffort($provider);
            if ($reasoningEffort !== null) {
                $payload['reasoning_effort'] = $reasoningEffort;
            }
            $post = json_encode($payload);

            $ai = new self($post);
            $ai->setProvider($provider);
            $ai->setTimeout(120);

            $res = $ai->request();
            if (Base::isError($res)) {
                return Base::retError("生成失败", $res);
            }

            // 清理可能的markdown代码块标记
            $content = $res['data'];
            $content = preg_replace('/^\s*```json\s*/', '', $content);
            $content = preg_replace('/\s*```\s*$/', '', $content);
            if (empty($content)) {
                return Base::retError("翻译结果为空");
            }

            // 解析JSON
            $parsedData = Base::json2array($content);
            if (!$parsedData || !isset($parsedData['jokes']) || !isset($parsedData['soups'])) {
                return Base::retError("生成内容格式错误", $content);
            }

            // 验证数据完整性
            if (!is_array($parsedData['jokes']) || !is_array($parsedData['soups'])) {
                return Base::retError("生成内容格式错误", $parsedData);
            }

            // 过滤空内容并确保有内容
            $jokes = array_filter(array_map('trim', $parsedData['jokes']));
            $soups = array_filter(array_map('trim', $parsedData['soups']));

            if (empty($jokes) || empty($soups)) {
                return Base::retError("生成内容为空", $parsedData);
            }

            return Base::retSuccess("success", [
                'jokes' => array_values($jokes),
                'soups' => array_values($soups),
                'total_jokes' => count($jokes),
                'total_soups' => count($soups),
                'generated_at' => date('Y-m-d H:i:s')
            ]);
        });

        if (Base::isError($result)) {
            Cache::forget($cacheKey);
        }
        return $result;
    }

    /**
     * 选择可用的文本模型配置
     * @return array|null
     */
    protected static function resolveTextProvider()
    {
        $setting = Base::setting('aibotSetting');
        if (!is_array($setting)) {
            $setting = [];
        }
        foreach (self::TEXT_MODEL_PRIORITY as $vendor) {
            $config = self::buildProviderConfig($setting, $vendor);
            if ($config) {
                return $config;
            }
        }
        return null;
    }

    /**
     * 构建指定厂商的请求参数
     * @param array $setting
     * @param string $vendor
     * @return array|null
     */
    protected static function buildProviderConfig(array $setting, string $vendor)
    {
        $key = trim((string)($setting[$vendor . '_key'] ?? ''));
        $baseUrl = trim((string)($setting[$vendor . '_base_url'] ?? ''));
        $agency = trim((string)($setting[$vendor . '_agency'] ?? ''));

        switch ($vendor) {
            case 'openai':
                if ($key === '') {
                    return null;
                }
                $baseUrl = $baseUrl ?: 'https://api.openai.com/v1';
                $model = self::resolveOpenAITextModel($setting);
                break;
            case 'ollama':
                if ($baseUrl === '') {
                    return null;
                }
                if ($key === '') {
                    $key = Base::strRandom(6);
                }
                $model = trim((string)($setting[$vendor . '_model'] ?? ''));
                break;
            case 'wenxin':
                $secret = trim((string)($setting['wenxin_secret'] ?? ''));
                if ($key === '' || $secret === '' || $baseUrl === '') {
                    return null;
                }
                $key = $key . ':' . $secret;
                $model = trim((string)($setting[$vendor . '_model'] ?? ''));
                break;
            default:
                if ($key === '' || $baseUrl === '') {
                    return null;
                }
                $model = trim((string)($setting[$vendor . '_model'] ?? ''));
                break;
        }

        if ($model === '') {
            return null;
        }

        return [
            'vendor' => $vendor,
            'model' => $model,
            'api_key' => $key,
            'base_url' => rtrim($baseUrl, '/'),
            'agency' => $agency,
        ];
    }

    /**
     * 解析 OpenAI 文本模型
     * @param array $setting
     * @return string
     */
    protected static function resolveOpenAITextModel(array $setting)
    {
        $models = Setting::AIBotModels2Array($setting['openai_models'] ?? '', true);
        if (in_array(self::OPENAI_DEFAULT_MODEL, $models, true)) {
            return self::OPENAI_DEFAULT_MODEL;
        }
        if (!empty($setting['openai_model'])) {
            return $setting['openai_model'];
        }
        return $models[0] ?? self::OPENAI_DEFAULT_MODEL;
    }

    /**
     * OpenAI 语音模型配置
     * @return array|null
     */
    protected static function resolveOpenAIAudioProvider()
    {
        $setting = Base::setting('aibotSetting');
        if (!is_array($setting)) {
            $setting = [];
        }
        $key = trim((string)($setting['openai_key'] ?? ''));
        if ($key === '') {
            return null;
        }
        $baseUrl = trim((string)($setting['openai_base_url'] ?? ''));
        $baseUrl = $baseUrl ?: 'https://api.openai.com/v1';
        $agency = trim((string)($setting['openai_agency'] ?? ''));

        return [
            'vendor' => 'openai',
            'model' => 'gpt-4o-mini-transcribe',
            'api_key' => $key,
            'base_url' => rtrim($baseUrl, '/'),
            'agency' => $agency,
        ];
    }

    /**
     * 获取 reasoning_effort 参数值
     * @param array $provider
     * @return string|null 返回 'none'/'low' 或 null（不需要此参数）
     */
    protected static function getReasoningEffort(array $provider): ?string
    {
        if (($provider['vendor'] ?? '') !== 'openai') {
            return null;
        }
        $model = $provider['model'] ?? '';

        // gpt-5.1 及之后版本支持 none
        if (preg_match('/^gpt-(\d+)\.(\d+)/', $model, $matches)) {
            $major = intval($matches[1]);
            $minor = intval($matches[2]);
            if ($major > 5 || ($major === 5 && $minor >= 1)) {
                return 'none';
            }
            if ($major === 5) {
                return 'low';
            }
        }

        // gpt-5 (无小版本号) 使用 low
        if (preg_match('/^gpt-(\d+)(?![.\d])/', $model, $matches)) {
            if (intval($matches[1]) >= 5) {
                return 'low';
            }
        }

        return null;
    }

    /**
     * 通过 OpenAI 兼容接口获取文本的 Embedding 向量
     *
     * @param string $text 需要转换的文本
     * @param bool $noCache 是否禁用缓存
     * @return array 返回结果，成功时 data 为向量数组
     */
    public static function getEmbedding($text, $noCache = false)
    {
        if (!Apps::isInstalled('ai')) {
            return Base::retError('应用「AI Assistant」未安装');
        }

        if (empty($text)) {
            return Base::retError('文本内容不能为空');
        }

        // 截断过长的文本（OpenAI 限制 8191 tokens，约 32K 字符）
        $text = mb_substr($text, 0, 30000);

        $cacheKey = "openAIEmbedding::" . md5($text);
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $provider = self::resolveEmbeddingProvider();
        if (!$provider) {
            return Base::retError("请先在「AI 助手」设置中配置支持 Embedding 的 AI 服务");
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addDays(7), function () use ($text, $provider) {
            $payload = [
                "model" => $provider['model'],
                "input" => $text,
            ];

            // 统一向量维度为 1536（与 Manticore 配置一致）
            // OpenAI、智谱等支持 dimensions 参数的厂商需要显式指定
            $supportsDimensions = in_array($provider['vendor'], ['openai', 'zhipu']);
            if ($supportsDimensions) {
                $payload['dimensions'] = 1536;
            }

            $post = json_encode($payload);

            $ai = new self($post);
            $ai->setProvider($provider);
            $ai->setUrlPath('/embeddings');
            $ai->setTimeout(30);

            $res = $ai->request(true);
            if (Base::isError($res)) {
                return Base::retError("Embedding 请求失败", $res);
            }

            $resData = Base::json2array($res['data']);
            if (empty($resData['data'][0]['embedding'])) {
                return Base::retError("Embedding 接口返回数据格式错误", $resData);
            }

            $embedding = $resData['data'][0]['embedding'];
            if (!is_array($embedding) || empty($embedding)) {
                return Base::retError("Embedding 向量为空");
            }

            return Base::retSuccess("success", $embedding);
        });

        if (Base::isError($result)) {
            Cache::forget($cacheKey);
        }

        return $result;
    }

    /**
     * 批量获取文本的 Embedding 向量
     * OpenAI API 原生支持批量输入，一次请求处理多个文本
     *
     * @param array $texts 文本数组（最多 100 条）
     * @param bool $noCache 是否禁用缓存
     * @return array 返回结果，成功时 data 为向量数组的数组（与输入顺序对应）
     */
    public static function getBatchEmbeddings(array $texts, $noCache = false)
    {
        if (!Apps::isInstalled('ai')) {
            return Base::retError('应用「AI Assistant」未安装');
        }

        if (empty($texts)) {
            return Base::retSuccess("success", []);
        }

        // 限制批量大小
        // OpenAI 限制：最多 2048 条，单次请求合计最多 300,000 tokens
        // 这里限制 500 条，假设平均每条 500 tokens，合计 250,000 tokens
        $texts = array_slice($texts, 0, 500);

        // 准备结果数组，并检查缓存
        $results = [];
        $uncachedTexts = [];
        $uncachedIndices = [];

        foreach ($texts as $index => $text) {
            if (empty($text)) {
                $results[$index] = [];
                continue;
            }

            // 截断过长的文本
            $text = mb_substr($text, 0, 30000);
            $texts[$index] = $text; // 更新截断后的文本

            $cacheKey = "openAIEmbedding::" . md5($text);

            if ($noCache) {
                Cache::forget($cacheKey);
            }

            // 检查缓存
            if (!$noCache && Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                if (Base::isSuccess($cached)) {
                    $results[$index] = $cached['data'];
                    continue;
                }
            }

            // 未命中缓存，加入待请求列表
            $uncachedTexts[] = $text;
            $uncachedIndices[] = $index;
        }

        // 如果所有文本都在缓存中
        if (empty($uncachedTexts)) {
            // 按原始顺序返回
            ksort($results);
            return Base::retSuccess("success", array_values($results));
        }

        // 获取 provider
        $provider = self::resolveEmbeddingProvider();
        if (!$provider) {
            return Base::retError("请先在「AI 助手」设置中配置支持 Embedding 的 AI 服务");
        }

        // 构建批量请求
        $payload = [
            "model" => $provider['model'],
            "input" => $uncachedTexts,
        ];

        $supportsDimensions = in_array($provider['vendor'], ['openai', 'zhipu']);
        if ($supportsDimensions) {
            $payload['dimensions'] = 1536;
        }

        $post = json_encode($payload);

        $ai = new self($post);
        $ai->setProvider($provider);
        $ai->setUrlPath('/embeddings');
        $ai->setTimeout(120); // 批量请求需要更长超时

        $res = $ai->request(true);
        if (Base::isError($res)) {
            return Base::retError("批量 Embedding 请求失败", $res);
        }

        $resData = Base::json2array($res['data']);
        if (empty($resData['data'])) {
            return Base::retError("Embedding 接口返回数据格式错误", $resData);
        }

        // 处理返回的向量并写入缓存
        foreach ($resData['data'] as $item) {
            $itemIndex = $item['index'] ?? null;
            if ($itemIndex === null || !isset($uncachedIndices[$itemIndex])) {
                continue;
            }

            $originalIndex = $uncachedIndices[$itemIndex];
            $embedding = $item['embedding'] ?? [];

            if (!empty($embedding) && is_array($embedding)) {
                $results[$originalIndex] = $embedding;
            } else {
                $results[$originalIndex] = [];
            }
        }

        // 填充未获取到向量的位置
        foreach ($uncachedIndices as $originalIndex) {
            if (!isset($results[$originalIndex])) {
                $results[$originalIndex] = [];
            }
        }

        // 按原始顺序返回
        ksort($results);
        return Base::retSuccess("success", array_values($results));
    }

    /**
     * 获取 Embedding 模型配置
     *
     * @return array|null
     */
    protected static function resolveEmbeddingProvider()
    {
        $setting = Base::setting('aibotSetting');
        if (!is_array($setting)) {
            $setting = [];
        }

        // 优先使用 OpenAI（支持 embedding 接口）
        $key = trim((string)($setting['openai_key'] ?? ''));
        if ($key !== '') {
            $baseUrl = trim((string)($setting['openai_base_url'] ?? ''));
            $baseUrl = $baseUrl ?: 'https://api.openai.com/v1';
            $agency = trim((string)($setting['openai_agency'] ?? ''));

            return [
                'vendor' => 'openai',
                'model' => 'text-embedding-3-small',
                'api_key' => $key,
                'base_url' => rtrim($baseUrl, '/'),
                'agency' => $agency,
            ];
        }

        $vendorDefaults = [
            'deepseek' => [
                'base_url' => 'https://api.deepseek.com',
                'model' => 'deepseek-embedding',
            ],
            'zhipu' => [
                'base_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'model' => 'embedding-3',
            ],
        ];

        // 尝试其他支持 embedding 的服务（如 deepseek、zhipu、qianwen 等）
        foreach ($vendorDefaults as $vendor => $defaults) {
            $key = trim((string)($setting[$vendor . '_key'] ?? ''));

            if ($key !== '') {
                $baseUrl = trim((string)($setting[$vendor . '_base_url'] ?? ''));
                $baseUrl = $baseUrl ?: $defaults['base_url'];  // 使用配置或默认值
                $agency = trim((string)($setting[$vendor . '_agency'] ?? ''));

                return [
                    'vendor' => $vendor,
                    'model' => $defaults['model'],
                    'api_key' => $key,
                    'base_url' => rtrim($baseUrl, '/'),
                    'agency' => $agency,
                ];
            }
        }

        return null;
    }
}
