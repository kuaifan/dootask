<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require __DIR__ . '/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;

require_once("config.php");


// 读取所有要翻译的内容
$originals = [];
$generateds = [];
foreach (['web', 'api'] as $type) {
    $content = file_exists("original-{$type}.txt") ? file_get_contents("original-{$type}.txt") : "";
    $array = array_values(array_filter(array_unique(explode("\n", $content))));
    $generateds[$type] = $array;
    $originals = array_merge($originals, $array);
}

// 判定是否存在translate.json文件
if (!file_exists("translate.json")) {
    print_r("translate.json not exists");
    exit;
}

$translations = []; // 翻译数据
$regrror = [];      // 正则匹配错误的数据
$redundants = [];   // 多余的数据
$needs = [];        // 需要翻译的数据

// 读取翻译数据
$tmps = json_decode(file_get_contents("translate.json"), true);
foreach ($tmps as $obj) {
    if (!isset($obj['key'])) {
        continue;
    }

    $currentKey = $obj['key'];
    $originalKey = preg_replace(["/\(%T\d+\)/", "/\(%M\d+\)/"], ["(*)", "(**)"], $currentKey);
    $translations[$originalKey] = $obj;

    if (!in_array($originalKey, $originals)) {
        // 多余的数据
        $redundants[$originalKey] = $obj;
        continue;
    }

    if (preg_match_all('/\(%[TM]\d+\)/', $currentKey, $matches)) {
        foreach ($matches[0] as $match) {
            foreach ($obj as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                if (!str_contains($v, $match)) {
                    // 正则匹配错误
                    $regrror[$originalKey] = [
                        $k => $v,
                        'match' => $match,
                        'key' => $currentKey,
                    ];
                    continue 2;
                }
            }
        }
    }
}

if (count($regrror) > 0) {
    print_r("正则匹配错误的数据:\n");
    print_r($regrror);
    exit();
}
if (count($redundants) > 0) {
    print_r("多余的数据:\n");
    print_r($redundants);
    exit();
}

// 需要翻译的数据
foreach ($originals as $text) {
    $key = trim($text);
    if (!isset($translations[$key])) {
        $needs[$key] = $key;
    }
}
if (count($needs) > 0) {
    $array = array_chunk($needs, 10, true);
    $success = [];
    $error = [];
    $done = 0;
    foreach ($array as $index => $keys) {
        // 生成翻译内容
        foreach ($keys as &$key) {
            $c = 1;
            $key = preg_replace_callback('/\((\*+)\)/', function ($m) use (&$c) {
                $label = strlen($m[1]) > 1 ? "M" : "T";
                return "(%" . $label . $c++ . ")";
            }, $key);
        }
        $content = implode("\n", $keys);

        // 开始翻译
        print_r("正在翻译：" . (count($keys) + $done) . "/" . count($needs) . "...\n");
        $openAi = new OpenAi(OPEN_AI_KEY);
        $openAi->setProxy(OPEN_AI_PROXY);
        $result = $openAi->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    "role" => "system",
                    "content" => <<<EOF
                        你是一个专业的翻译器，翻译的结果尽量符合 “项目任务管理系统” 的使用，请将提供的内容按每行一个翻译成：

                        ```json
                        [
                            {
                                "key": "",      // 原文本
                                "zh": "",       // 留空(不用翻译)
                                "zh-CHT": "",   // 繁体中文
                                "en": "",       // 英语
                                "ko": "",       // 韩语
                                "ja": "",       // 日语
                                "de": "",       // 德语
                                "fr": "",       // 法语
                                "id": "",       // 印度尼西亚语
                                "ru": ""        // 俄语
                            }
                        ]
                        ```

                        请注意：(%T1)、(%T2)、(%T3)、(%M1)、(%M2) ...... 这类以 `小括号(%+内容)` 的字符组合是一个变量，翻译时请保留。

                        例子1：
                        原文：此(%T1)已经处于【(%T2)】共享文件夹中，无法重复共享。
                        翻译成英语：This (%T1) is already in the 【(%T2)】 shared folder and cannot be shared again。

                        例子2：
                        原文：(%T1)的周报[(%T2)][(%T3)月第(%T4)周]
                        翻译成英语：Weekly report of (%T1) [(%T2)] [(Week (%T4) of (%T3) month)]

                        例子3：
                        原文：(%T1)提交的「(%M2)」待你审批
                        翻译成英语：'(%M2)' submitted by (%T1) is waiting for your approval

                        例子4：
                        原文：您发起的「(%M1)」已通过
                        翻译成英语：The '(%M1)' you initiated has been approved
                        EOF,
                ],
                [
                    "role" => "user",
                    "content" => $content,
                ],
            ],
            'temperature' => 1.0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        // 处理结果
        $obj = json_decode($result);
        $txt = preg_replace('/(^\s*```json\s*|\s*```\s*$)/', "", $obj->choices[0]->message->content);
        $txt = preg_replace('/\(％([TM]\d+)\)/', '(%$1)', $txt);
        $arr = json_decode($txt, true);
        if (!$arr || !is_array($arr)) {
            $error = array_merge($error, array_flip($keys));
            print_r("翻译失败：\n" . $content . "\n\n");
            file_put_contents("translate-gpt.log", json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
            continue;
        }

        // 验证结果
        foreach ($arr as $item) {
            if (empty($item['key'])) {
                print_r("翻译结果不符合规范：key为空。\n");
                print_r($item);
                continue;
            }
            foreach (['key', 'zh', 'zh-CHT', 'en', 'ko', 'ja', 'de', 'fr', 'id', 'ru'] as $lang) {
                if (!isset($item[$lang])) {
                    print_r("翻译结果不符合规范：{$item['key']}，缺少：{$lang} 的值。\n");
                    continue 2;
                }
            }
            $currentKey = $item['key'];
            $originalKey = preg_replace(["/\(%T\d+\)/", "/\(%M\d+\)/"], ["(*)", "(**)"], $currentKey);
            if (preg_match_all('/\(%[TM]\d+\)/', $currentKey, $matches)) {
                foreach ($matches[0] as $match) {
                    foreach ($item as $k => $v) {
                        if (empty($v)) {
                            continue;
                        }
                        if (!str_contains($v, $match)) {
                            // 正则匹配错误
                            $error[$originalKey] = [
                                'key' => $currentKey,
                                $k => $v,
                                'match' => $match,
                            ];
                            continue 3;
                        }
                    }
                }
            }

            $item['zh'] = "";
            $translations[$originalKey] = $item;
            $success[$originalKey] = $item;
        }
        print_r("翻译完成：" . (count($keys) + $done) . "/" . count($needs) . "\n\n");
        $done += count($keys);
    }

    if (count($error) > 0) {
        print_r("正则匹配错误的数据:\n");
        print_r(json_encode(array_values($error), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n");
    }

    // 保存翻译结果
    file_put_contents("translate.json", json_encode(array_values($translations), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    print_r("----------------\n\n");
    print_r("总翻译：" . count($needs) . " 条\n");
    print_r("成功：" . count($success) . " 条\n");
    print_r("错误：" . count($error) . " 条\n\n");
    print_r("----------------\n\n");
}

// 生成前端使用的文件
foreach ($generateds as $type => $array) {
    $datas = [];
    foreach ($array as $text) {
        $text = trim($text);
        if (isset($translations[$text])) {
            $datas[] = $translations[$text];
        }
    }
    // 按长度排序
    $inOrder = [];
    foreach ($datas as $index => $item) {
        if (preg_match('/\(%[TM]\d+\)/', $item['key'])) {
            $inOrder[$index] = strlen($item['key']);
        } else {
            $inOrder[$index] = strlen($item['key']) + 10000000000;
        }
    }
    array_multisort($inOrder, SORT_DESC, $datas);
    // 合成数组
    $results = [];
    $index = 0;
    foreach ($datas as $items) {
        foreach ($items as $kk => $item) {
            if (!isset($results)) {
                $results[$kk] = [];
            }
            $results[$kk][] = $item;
        }
    }
    // 生成文件
    if ($type === 'api') {
        if (!is_dir("../public/language/api")) {
            mkdir("../public/language/api", 0777, true);
        }
        foreach ($results as $kk => $item) {
            $file = "../public/language/api/$kk.json";
            file_put_contents($file, json_encode($item, JSON_UNESCAPED_UNICODE));
        }
    } elseif ($type === 'web') {
        if (!is_dir("../public/language/web")) {
            mkdir("../public/language/web", 0777, true);
        }
        foreach ($results as $kk => $item) {
            $file = "../public/language/web/$kk.js";
            file_put_contents($file, "if(typeof window.LANGUAGE_DATA===\"undefined\")window.LANGUAGE_DATA={};window.LANGUAGE_DATA[\"{$kk}\"]=" . json_encode($item, JSON_UNESCAPED_UNICODE));
        }
    }
    print_r("[$type] total: " . count($results['key']) . "\n");
}

print_r("\n任务结束\n");
