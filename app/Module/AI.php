<?php

namespace App\Module;

use App\Models\Setting;
use Cache;
use Carbon\Carbon;

/**
 * AI 助手模块
 */
class AI
{
    protected $post = [];
    protected $headers = [];
    protected $urlPath = '';
    protected $timeout = 30;

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
     * 请求 AI 接口
     * @param bool $resRaw 是否返回原始数据
     * @return array
     */
    public function request($resRaw = false)
    {
        $aiSetting = Base::setting('aiSetting');
        if (!Setting::AIOpen()) {
            return Base::retError("AI 助手未开启");
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $aiSetting['ai_api_key'],
        ];
        if ($aiSetting['ai_proxy']) {
            $headers['CURLOPT_PROXY'] = $aiSetting['ai_proxy'];
            $headers['CURLOPT_PROXYTYPE'] = str_contains($aiSetting['ai_proxy'], 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }
        $headers = array_merge($headers, $this->headers);

        $url = $aiSetting['ai_api_url'] ?: 'https://api.openai.com/v1';
        $url = $url . ($this->urlPath ?: '/chat/completions');

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

    /** ******************************************************************************************** */
    /** ******************************************************************************************** */
    /** ******************************************************************************************** */

    /**
     * 通过 openAI 语音转文字
     * @param string $filePath 语音文件路径
     * @param array $extParams 扩展参数
     * @param bool $noCache 是否禁用缓存
     * @return array
     */
    public static function transcriptions($filePath, $extParams = [], $noCache = false)
    {
        if (!file_exists($filePath)) {
            return Base::retError("语音文件不存在");
        }
        $systemSetting = Base::setting('system');
        if ($systemSetting['voice2text'] !== 'open') {
            return Base::retError("语音转文字功能未开启");
        }

        $cacheKey = "openAItranscriptions::" . md5($filePath . '_' . Base::array2json($extParams));
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addDays(), function () use ($extParams, $filePath) {
            $post = array_merge($extParams, [
                'file' => new \CURLFile($filePath),
                'model' => 'whisper-1',
            ]);
            $header = [
                'Content-Type' => 'multipart/form-data',
            ];

            $ai = new self($post, $header);
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
        $systemSetting = Base::setting('system');
        if ($systemSetting['translation'] !== 'open') {
            return Base::retError("翻译功能未开启");
        }

        $cacheKey = "openAItranslations::" . md5($text . '_' . $targetLanguage);
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addDays(7), function () use ($text, $targetLanguage) {
            $post = json_encode([
                "model" => "gpt-5-nano",
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
            ]);

            $ai = new self($post);
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
        $cacheKey = "openAIGenerateTitle::" . md5($text);
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addHours(24), function () use ($text) {
            $post = json_encode([
                "model" => "gpt-5-nano",
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
            ]);

            $ai = new self($post);
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
     * 通过 openAI 生成任务标题和描述
     * @param string $text 任务描述
     * @param array $context 上下文信息
     * @return array
     */
    public static function generateTask($text, $context = [])
    {
        // 构建上下文提示信息
        $contextPrompt = self::buildTaskContextPrompt($context);

        $post = json_encode([
            "model" => "gpt-5-nano",
            "messages" => [
                [
                    "role" => "system",
                    "content" => <<<EOF
                        你是一个专业的任务管理专家，擅长将想法和需求转化为清晰、可执行的项目任务。

                        任务生成要求：
                        1. 根据输入内容分析并生成合适的任务标题和详细描述
                        2. 标题要简洁明了，准确概括任务核心目标，长度控制在8-30个字符
                        3. 描述需覆盖任务背景、具体要求、交付标准、风险提示等关键信息
                        4. 描述内容使用Markdown格式，合理组织标题、列表、加粗等结构
                        5. 内容需适配项目管理系统，表述专业、逻辑清晰，并与用户输入语言保持一致
                        6. 优先遵循用户在输入中给出的风格、长度或复杂度要求；默认情况下将详细描述控制在120-200字内，如用户要求简单或简短，则控制在80-120字内
                        7. 当任务具有多个执行步骤、阶段或协作角色时，请拆解出 2-6 个关键子任务；如无必要，可返回空数组
                        8. 子任务应聚焦单一可执行动作，名称控制在8-30个字符内，避免重复和含糊表述

                        返回格式要求：
                        必须严格按照以下 JSON 结构返回，禁止输出额外文字或 Markdown 代码块标记；即使某项为空，也保留对应字段：
                        {
                            "title": "任务标题",
                            "content": "任务的详细描述内容，使用Markdown格式，根据实际情况组织结构",
                            "subtasks": [
                                "子任务名称1",
                                "子任务名称2"
                            ]
                        }

                        内容格式建议（非强制）：
                        - 可以使用标题、列表、加粗等Markdown格式
                        - 可以包含任务背景、具体要求、验收标准等部分
                        - 根据任务性质灵活组织内容结构
                        - 仅在确有必要时生成子任务，并确保每个子任务都是独立、可执行、便于追踪的动作
                        - 若用户明确要求简洁或简单，保持描述紧凑，避免添加冗余段落或重复信息

                        上下文信息处理指南：
                        - 如果已有标题和内容，优先考虑优化改进而非完全重写
                        - 如果使用了任务模板，严格按照模板的结构和格式要求生成
                        - 如果已设置负责人或时间计划，在任务描述中体现相关要求
                        - 根据优先级等级调整任务的紧急程度和详细程度

                        注意事项：
                        - 标题要体现任务的核心动作和目标
                        - 描述要包含足够的细节让执行者理解任务
                        - 如果涉及技术开发，要明确技术要求和实现方案
                        - 如果涉及设计，要说明设计要求和期望效果
                        - 如果涉及测试，要明确测试范围和验收标准
                        EOF
                ],
                [
                    "role" => "user",
                    "content" => $contextPrompt . "\n\n请根据以上上下文和以下用户描述生成一个完整的项目任务（包含标题和详细描述）：\n\n" . $text
                ]
            ],
        ]);

        $ai = new self($post);
        $ai->setTimeout(60);

        $res = $ai->request();
        if (Base::isError($res)) {
            return Base::retError("任务生成失败", $res);
        }

        // 清理可能的markdown代码块标记
        $content = $res['data'];
        $content = preg_replace('/^\s*```json\s*/', '', $content);
        $content = preg_replace('/\s*```\s*$/', '', $content);

        if (empty($content)) {
            return Base::retError("任务生成结果为空");
        }

        // 解析JSON
        $parsedData = Base::json2array($content);
        if (!$parsedData || !isset($parsedData['title']) || !isset($parsedData['content'])) {
            return Base::retError("任务生成格式错误", $content);
        }

        $title = trim($parsedData['title']);
        $markdownContent = trim($parsedData['content']);
        $rawSubtasks = $parsedData['subtasks'] ?? [];

        if (empty($title) || empty($markdownContent)) {
            return Base::retError("生成的任务标题或内容为空", $parsedData);
        }

        $subtasks = [];
        if (is_array($rawSubtasks)) {
            foreach ($rawSubtasks as $raw) {
                if (is_array($raw)) {
                    $name = trim($raw['title'] ?? $raw['name'] ?? '');
                } else {
                    $name = trim($raw);
                }

                if (!empty($name)) {
                    $subtasks[] = $name;
                }

                if (count($subtasks) >= 8) {
                    break;
                }
            }
        }

        return Base::retSuccess("success", [
            'title' => $title,
            'content' => Base::markdown2html($markdownContent),  // 将 Markdown 转换为 HTML
            'subtasks' => $subtasks
        ]);
    }

    /**
     * 构建任务生成的上下文提示信息
     * @param array $context 上下文信息
     * @return string
     */
    private static function buildTaskContextPrompt($context)
    {
        $prompts = [];

        // 当前任务信息
        if (!empty($context['current_title']) || !empty($context['current_content'])) {
            $prompts[] = "## 当前任务信息";
            if (!empty($context['current_title'])) {
                $prompts[] = "当前标题：" . $context['current_title'];
            }
            if (!empty($context['current_content'])) {
                $prompts[] = "当前内容：" . $context['current_content'];
            }
            $prompts[] = "请在此基础上优化改进，而不是完全重写。";
        }

        // 任务模板信息
        if (!empty($context['template_name']) || !empty($context['template_content'])) {
            $prompts[] = "## 任务模板要求";
            if (!empty($context['template_name'])) {
                $prompts[] = "模板名称：" . $context['template_name'];
            }
            if (!empty($context['template_content'])) {
                $prompts[] = "模板内容结构：" . $context['template_content'];
            }
            $prompts[] = "请严格按照此模板的结构和格式要求生成内容。";
        }

        // 项目状态信息
        $statusInfo = [];
        if (!empty($context['has_owner'])) {
            $statusInfo[] = "已设置负责人";
        }
        if (!empty($context['has_time_plan'])) {
            $statusInfo[] = "已设置计划时间";
        }
        if (!empty($context['priority_level'])) {
            $statusInfo[] = "优先级：" . $context['priority_level'];
        }

        if (!empty($statusInfo)) {
            $prompts[] = "## 任务状态";
            $prompts[] = implode("，", $statusInfo);
            $prompts[] = "请在任务描述中体现相应的要求和约束。";
        }

        return empty($prompts) ? "" : implode("\n", $prompts);
    }

    /**
     * 通过 openAI 生成职场笑话、心灵鸡汤
     * @param bool $noCache 是否禁用缓存
     * @return array 返回20个笑话和20个心灵鸡汤
     */
    public static function generateJokeAndSoup($noCache = false)
    {
        $cacheKey = "openAIJokeAndSoup::" . md5(date('Y-m-d'));
        if ($noCache) {
            Cache::forget($cacheKey);
        }

        $result = Cache::remember($cacheKey, Carbon::now()->addHours(6), function () {
            $post = json_encode([
                "model" => "gpt-5-nano",
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
            ]);

            $ai = new self($post);
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
     * 获取 ollama 模型
     * @param $baseUrl
     * @param $key
     * @param $agency
     * @return array
     */
    public static function ollamaModels($baseUrl, $key = null, $agency = null)
    {
        $extra = [
            'Content-Type' => 'application/json',
        ];
        if ($key) {
            $extra['Authorization'] = 'Bearer ' . $key;
        }
        if ($agency) {
            $extra['CURLOPT_PROXY'] = $agency;
            $extra['CURLOPT_PROXYTYPE'] = str_contains($agency, 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }
        $res = Ihttp::ihttp_request(rtrim($baseUrl, '/') . '/api/tags', [], $extra, 15);
        if (Base::isError($res)) {
            return Base::retError("获取失败", $res);
        }
        $resData = Base::json2array($res['data']);
        if (empty($resData['models'])) {
            return Base::retError("获取失败", $resData);
        }
        $models = [];
        foreach ($resData['models'] as $model) {
            if ($model['name'] !== $model['model']) {
                $models[] = "{$model['model']} | {$model['name']}";
            } else {
                $models[] = $model['model'];
            }
        }
        return Base::retSuccess("success", [
            'models' => $models,
            'original' => $resData['models']
        ]);
    }
}
