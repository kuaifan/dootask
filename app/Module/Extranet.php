<?php

namespace App\Module;

use App\Models\Setting;
use Cache;
use Carbon\Carbon;

/**
 * 外网资源请求
 */
class Extranet
{
    /**
     * 通过 openAI 语音转文字
     * @param string $filePath
     * @param array $extParams
     * @return array
     */
    public static function openAItranscriptions($filePath, $extParams = [])
    {
        if (!file_exists($filePath)) {
            return Base::retError("语音文件不存在");
        }
        $systemSetting = Base::setting('system');
        $aiSetting = Base::setting('aiSetting');
        if ($systemSetting['voice2text'] !== 'open' || !Setting::AIOpen()) {
            return Base::retError("语音转文字功能未开启");
        }

        $extra = [
            'Content-Type' => 'multipart/form-data',
            'Authorization' => 'Bearer ' . $aiSetting['ai_api_key'],
        ];
        if ($aiSetting['ai_proxy']) {
            $extra['CURLOPT_PROXY'] = $aiSetting['ai_proxy'];
            $extra['CURLOPT_PROXYTYPE'] = str_contains($aiSetting['ai_proxy'], 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }

        $cacheKey = "openAItranscriptions::" . md5($filePath . '_' . Base::array2json($extra) . '_' . Base::array2json($extParams));
        $result = Cache::remember($cacheKey, Carbon::now()->addDays(), function () use ($aiSetting, $extra, $extParams, $filePath) {
            $post = array_merge($extParams, [
                'file' => new \CURLFile($filePath),
                'model' => 'whisper-1',
            ]);

            $res = Ihttp::ihttp_request(($aiSetting['ai_api_url'] ?: 'https://api.openai.com/v1') . '/audio/transcriptions', $post, $extra, 15);
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
     * @return array
     */
    public static function openAItranslations($text, $targetLanguage)
    {
        $systemSetting = Base::setting('system');
        $aiSetting = Base::setting('aiSetting');
        if ($systemSetting['translation'] !== 'open' || !Setting::AIOpen()) {
            return Base::retError("翻译功能未开启");
        }

        $extra = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $aiSetting['ai_api_key'],
        ];
        if ($aiSetting['ai_proxy']) {
            $extra['CURLOPT_PROXY'] = $aiSetting['ai_proxy'];
            $extra['CURLOPT_PROXYTYPE'] = str_contains($aiSetting['ai_proxy'], 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }

        $cacheKey = "openAItranslations::" . md5($text . '_' . $targetLanguage . '_' . ($aiSetting['ai_api_key'] ?? ''));
        $result = Cache::remember($cacheKey, Carbon::now()->addDays(7), function () use ($aiSetting, $extra, $text, $targetLanguage) {
            $post = json_encode([
                "model" => "gpt-4.1-nano",
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
                "temperature" => 0.2,
                "max_tokens" => max(1000, intval(mb_strlen($text) * 1.5))
            ]);

            $res = Ihttp::ihttp_request(($aiSetting['ai_api_url'] ?: 'https://api.openai.com/v1') . '/chat/completions', $post, $extra, 60);
            if (Base::isError($res)) {
                return Base::retError("翻译请求失败", $res);
            }
            $resData = Base::json2array($res['data']);
            if (empty($resData['choices'])) {
                return Base::retError("翻译响应格式错误", $resData);
            }
            $translatedText = $resData['choices'][0]['message']['content'];
            $translatedText = trim($translatedText);
            if (empty($translatedText)) {
                return Base::retError("翻译结果为空");
            }

            return Base::retSuccess("success", [
                'translated_text' => $translatedText,
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
     * @return array
     */
    public static function openAIGenerateTitle($text)
    {
        $aiSetting = Base::setting('aiSetting');
        if (!Setting::AIOpen()) {
            return Base::retError("AI接口未配置");
        }

        $extra = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $aiSetting['ai_api_key'],
        ];
        if ($aiSetting['ai_proxy']) {
            $extra['CURLOPT_PROXY'] = $aiSetting['ai_proxy'];
            $extra['CURLOPT_PROXYTYPE'] = str_contains($aiSetting['ai_proxy'], 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }

        $cacheKey = "openAIGenerateTitle::" . md5($text . '_' . Base::array2json($extra));
        $result = Cache::remember($cacheKey, Carbon::now()->addHours(24), function () use ($aiSetting, $extra, $text) {
            $post = json_encode([
                "model" => "gpt-4.1-nano",
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
                "temperature" => 0.3,
                "max_tokens" => 100
            ]);

            $res = Ihttp::ihttp_request(($aiSetting['ai_api_url'] ?: 'https://api.openai.com/v1') . '/chat/completions', $post, $extra, 10);
            if (Base::isError($res)) {
                return Base::retError("标题生成失败", $res);
            }
            $resData = Base::json2array($res['data']);
            if (empty($resData['choices'])) {
                return Base::retError("标题生成失败", $resData);
            }
            $result = $resData['choices'][0]['message']['content'];
            $result = trim($result);
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
     * @return array 返回20个笑话和20个心灵鸡汤
     */
    public static function openAIGenJokeAndSoup()
    {
        $aiSetting = Base::setting('aiSetting');
        if (!Setting::AIOpen()) {
            return Base::retError("AI接口未配置");
        }

        $extra = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $aiSetting['ai_api_key'],
        ];
        if ($aiSetting['ai_proxy']) {
            $extra['CURLOPT_PROXY'] = $aiSetting['ai_proxy'];
            $extra['CURLOPT_PROXYTYPE'] = str_contains($aiSetting['ai_proxy'], 'socks') ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP;
        }

        $cacheKey = "openAIJokeAndSoup::" . md5(date('Y-m-d'));
        $result = Cache::remember($cacheKey, Carbon::now()->addHours(6), function () use ($aiSetting, $extra) {
            $post = json_encode([
                "model" => "gpt-4.1-nano",
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
                "temperature" => 0.8
            ]);

            $res = Ihttp::ihttp_request(($aiSetting['ai_api_url'] ?: 'https://api.openai.com/v1') . '/chat/completions', $post, $extra, 120);
            if (Base::isError($res)) {
                return Base::retError("生成失败", $res);
            }
            $resData = Base::json2array($res['data']);
            if (empty($resData['choices'])) {
                return Base::retError("生成失败", $resData);
            }

            // 清理可能的markdown代码块标记
            $content = $resData['choices'][0]['message']['content'];
            $content = preg_replace('/^\s*```json\s*/', '', $content);
            $content = preg_replace('/\s*```\s*$/', '', $content);
            $content = trim($content);

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
                'jokes' => array_values($jokes), // 重新索引数组
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

    /**
     * 判断是否工作日
     * @param string $Ymd 年月日（如：20220102）
     * @return int
     * 0: 工作日
     * 1: 非工作日
     * 2: 获取不到远程数据的非工作日（周六、日）
     * 所以可以用>0来判断是否工作日
     */
    public static function isHoliday(string $Ymd): int
    {
        $time = strtotime($Ymd . " 00:00:00");
        $holidayKey = "holiday::" . date("Ym", $time);
        $holidayData = Cache::remember($holidayKey, now()->addMonth(), function () use ($time) {
            $apiMonth = date("Ym", $time);
            $apiResult = Ihttp::ihttp_request("https://api.apihubs.cn/holiday/get?field=date&month={$apiMonth}&workday=2&size=31", [], [], 20);
            if (Base::isError($apiResult)) {
                info('[holiday] get error');
                return [];
            }
            $apiResult = Base::json2array($apiResult['data']);
            if ($apiResult['code'] !== 0) {
                info('[holiday] result error');
                return [];
            }
            return array_map(function ($item) {
                return $item['date'];
            }, $apiResult['data']['list']);
        });
        if (empty($holidayData)) {
            Cache::forget($holidayKey);
            return in_array(date("w", $time), [0, 6]) ? 2 : 0;
        }
        return in_array($Ymd, $holidayData) ? 1 : 0;
    }

    /**
     * Drawio 图标搜索
     * @param $query
     * @param $page
     * @param $size
     * @return array
     */
    public static function drawioIconSearch($query, $page, $size): array
    {
        $result = self::curl("https://app.diagrams.net/iconSearch?q={$query}&p={$page}&c={$size}", 15 * 86400);
        if ($result = Base::json2array($result)) {
            return $result;
        }
        return [
            'icons' => [],
            'total_count' => 0
        ];
    }

    /**
     * 获取搜狗表情包
     * @param $keyword
     * @return array
     */
    public static function sticker($keyword)
    {
        $data = self::curl("https://pic.sogou.com/napi/wap/searchlist", 1800, 15, [], [
            'CURLOPT_CUSTOMREQUEST' => 'POST',
            'CURLOPT_POSTFIELDS' => json_encode([
                "initQuery" => $keyword . " 表情",
                "queryFrom" => "wap",
                "ie" => "utf8",
                "keyword" => $keyword . " 表情",
                // "mode" => 20,
                "showMode" => 0,
                "start" => 1,
                "reqType" => "client",
                "reqFrom" => "wap_result",
                "prevIsRedis" => "n",
                "pagetype" => 0,
                "amsParams" => []
            ]),
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json',
                'Referer: https://pic.sogou.com/'
            ]
        ]);
        $data = Base::json2array($data);
        if ($data['status'] === 0 && $data['data']['picResult']['items']) {
            $data = $data['data']['picResult']['items'];
            $data = array_filter($data, function ($item) {
                return intval($item['thumbHeight']) > 10 && intval($item['thumbWidth']) > 10;
            });
            return array_map(function ($item) {
                return [
                    'name' => $item['title'],
                    'src' => $item['thumbUrl'],
                    'height' => $item['thumbHeight'],
                    'width' => $item['thumbWidth'],
                ];
            }, $data);
        }
        return [];
    }

    /**
     * @param $url
     * @param int $cacheSecond 缓存时间（秒），如果结果为空则缓存有效30秒
     * @param int $timeout
     * @param array $post
     * @param array $extra
     * @return string
     */
    private static function curl($url, int $cacheSecond = 0, int $timeout = 15, array $post = [], array $extra = []): string
    {
        if ($cacheSecond > 0) {
            $key = "curlCache::" . md5($url) . "::" . md5(json_encode($post)) . "::" . md5(json_encode($extra));
            $content = Cache::remember($key, Carbon::now()->addSeconds($cacheSecond), function () use ($extra, $post, $cacheSecond, $key, $timeout, $url) {
                $result = Ihttp::ihttp_request($url, $post, $extra, $timeout);
                $content = Base::isSuccess($result) ? trim($result['data']) : '';
                if (empty($content) && $cacheSecond > 30) {
                    Cache::put($key, "", Carbon::now()->addSeconds(30));
                }
                return $content;
            });
        } else {
            $result = Ihttp::ihttp_request($url, $post, $extra, $timeout);
            $content = Base::isSuccess($result) ? trim($result['data']) : '';
        }
        //
        return $content;
    }
}
