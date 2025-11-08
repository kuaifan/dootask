<?php

namespace App\Module;

use App\Models\Report;
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
                "model" => "gpt-5-mini",
                "reasoning_effort" => "minimal",
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
                "model" => "gpt-5-mini",
                "reasoning_effort" => "minimal",
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
     * 对工作汇报内容进行分析
     * @param Report $report
     * @param array $context
     * @return array
     */
    public static function analyzeReport(Report $report, array $context = [])
    {
        $prompt = self::buildReportAnalysisPrompt($report, $context);
        if ($prompt === '') {
            return Base::retError("报告内容为空，无法进行分析");
        }

        $post = json_encode([
            "model" => "gpt-5-mini",
            "reasoning_effort" => "minimal",
            "messages" => [
                [
                    "role" => "system",
                    "content" => <<<EOF
                        你是一名经验丰富的团队管理顾问，擅长阅读和分析员工提交的工作汇报，能够快速提炼重点并给出可执行建议。

                        输出要求：
                        1. 使用简洁的 Markdown 结构（标题、无序列表、引用等），不要使用代码块或 JSON
                        2. 先给出整体概览，再列出具体亮点、风险或问题，以及明确的改进建议
                        3. 如有数据或目标，应评估其完成情况和后续跟进要点
                        4. 语气保持专业、客观，中立，不过度夸赞或批评
                        5. 控制在 200-400 字之间，必要时可略微增减，但保持紧凑
                        EOF
                ],
                [
                    "role" => "user",
                    "content" => $prompt,
                ],
            ],
        ]);

        $ai = new self($post);
        $ai->setTimeout(60);

        $res = $ai->request();
        if (Base::isError($res)) {
            return Base::retError("工作汇报分析失败", $res);
        }

        $content = trim($res['data']);
        $content = preg_replace('/^\s*```(?:markdown|md|text)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/', '', $content);
        $content = trim($content);

        if ($content === '') {
            return Base::retError("工作汇报分析结果为空");
        }

        return Base::retSuccess("success", [
            'text' => $content,
        ]);
    }

    /**
     * 整理优化工作汇报内容
     * @param string $markdown 用户当前的工作汇报（Markdown）
     * @param array $context 上下文信息
     * @return array
     */
    public static function organizeReportContent(string $markdown, array $context = [])
    {
        $markdown = trim((string)$markdown);
        if ($markdown === '') {
            return Base::retError("工作汇报内容不能为空");
        }

        $prompt = self::buildReportOrganizePrompt($markdown, $context);
        if ($prompt === '') {
            return Base::retError("整理内容为空");
        }

        $post = json_encode([
            "model" => "gpt-5-mini",
            "reasoning_effort" => "minimal",
            "messages" => [
                [
                    "role" => "system",
                    "content" => <<<EOF
                        你是一名资深的职场写作顾问，擅长根据已有的工作汇报草稿进行整理、结构化和措辞优化。

                        工作任务：
                        1. 保留草稿中的事实、数据和结论，确保信息准确无误
                        2. 重新组织结构，让内容清晰分段（如「重点进展」「成果亮点」「问题与风险」「后续计划」等），并按照草稿中的时间范围或类型进行表达
                        3. 用简洁、专业且积极的语气描述，并突出可复用的要点
                        4. 支持使用 Markdown 标题、列表、引用、表格等语法增强可读性，但不要返回 HTML 或代码块
                        5. 若草稿信息不完整，可合理推测缺失项并以占位符提示（如「待补充」），不要臆造细节

                        输出要求：
                        - 仅返回整理后的 Markdown 正文内容，并用于直接替换原草稿
                        - 不得输出任何汇报名称、汇报对象、汇报类型或其他元信息，即便草稿或上下文中存在这些字段
                        - 不加额外说明、指引、总结或前缀后缀
                        - 内容保持精炼、结构清晰
                        EOF
                ],
                [
                    "role" => "user",
                    "content" => $prompt,
                ],
            ],
        ]);

        $ai = new self($post);
        $ai->setTimeout(60);

        $res = $ai->request();
        if (Base::isError($res)) {
            return Base::retError("汇报整理失败", $res);
        }

        $content = trim($res['data']);
        $content = preg_replace('/^\s*```(?:markdown|md|text)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/', '', $content);
        $content = trim($content);

        if ($content === '') {
            return Base::retError("汇报整理结果为空");
        }

        return Base::retSuccess("success", [
            'text' => $content,
        ]);
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
                "model" => "gpt-5-mini",
                "reasoning_effort" => "minimal",
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
     * 构建工作汇报分析的提示词
     * @param Report $report
     * @param array $context
     * @return string
     */
    private static function buildReportAnalysisPrompt(Report $report, array $context = []): string
    {
        $sections = [];

        $metaLines = [];
        $metaLines[] = "标题：" . ($report->title ?: "（未填写）");
        $metaLines[] = "类型：" . match ($report->type) {
            Report::WEEKLY => "周报",
            Report::DAILY => "日报",
            default => $report->type,
        };
        if ($report->created_at) {
            $createdAt = $report->created_at instanceof Carbon
                ? $report->created_at->toDateTimeString()
                : (string)$report->created_at;
            $metaLines[] = "提交时间：" . $createdAt;
        }
        if (!empty($context['viewer_role'])) {
            $metaLines[] = "查看人角色：" . trim($context['viewer_role']);
        }
        if (!empty($context['viewer_name'])) {
            $metaLines[] = "查看人：" . trim($context['viewer_name']);
        }
        if (!empty($metaLines)) {
            $sections[] = "### 基础信息\n" . implode("\n", array_map(function ($line) {
                return "- " . $line;
            }, $metaLines));
        }

        if (!empty($context['focus']) && is_array($context['focus'])) {
            $focusItems = array_filter(array_map('trim', $context['focus']));
            if (!empty($focusItems)) {
                $sections[] = "### 关注重点\n" . implode("\n", array_map(function ($line) {
                    return "- " . $line;
                }, $focusItems));
            }
        } elseif (!empty($context['focus_note'])) {
            $sections[] = "### 关注重点\n- " . trim($context['focus_note']);
        }

        if (!empty($context['previous_feedback'])) {
            $sections[] = "### 历史反馈\n" . trim($context['previous_feedback']);
        }

        $contentMarkdown = trim(Base::html2markdown($report->content));
        if ($contentMarkdown !== '') {
            $sections[] = "### 汇报正文\n" . $contentMarkdown;
        }

        return trim(implode("\n\n", array_filter($sections)));
    }

    /**
     * 构建工作汇报整理的提示词
     * @param string $markdown
     * @param array $context
     * @return string
     */
    private static function buildReportOrganizePrompt(string $markdown, array $context = []): string
    {
        $sections = [];

        $infoLines = [];
        if (!empty($context['title'])) {
            $infoLines[] = "汇报标题：" . trim($context['title']);
        }
        if (!empty($context['type'])) {
            $typeLabel = match ($context['type']) {
                Report::WEEKLY => "周报",
                Report::DAILY => "日报",
                default => $context['type'],
            };
            $infoLines[] = "汇报类型：" . $typeLabel;
        }
        if (!empty($context['focus']) && is_array($context['focus'])) {
            $focusItems = array_filter(array_map('trim', $context['focus']));
            if (!empty($focusItems)) {
                $sections[] = "### 需要重点整理的方向\n" . implode("\n", array_map(function ($line) {
                    return "- " . $line;
                }, $focusItems));
            }
        }

        if (!empty($infoLines)) {
            $sections[] = "### 汇报背景（仅供参考，请勿写入输出）\n" . implode("\n", array_map(function ($line) {
                return "- " . $line;
            }, $infoLines));
        }

        $sections[] = "### 原始汇报草稿（请整理后仅输出正文内容）\n" . $markdown;

        return trim(implode("\n\n", array_filter($sections)));
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
