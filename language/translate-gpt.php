<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require __DIR__ . '/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;

require_once("config.php");


// 读取所有要翻译的内容
$array = [];
foreach (['api', 'web'] as $type) {
    $content = file_exists("original-{$type}.txt") ? file_get_contents("original-{$type}.txt") : "";
    $array = array_merge($array, array_values(array_filter(array_unique(explode("\n", $content)))));
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

$tmps = json_decode(file_get_contents("translate.json"), true);
foreach ($tmps as $tmp) {
    if (!isset($tmp['key'])) {
        continue;
    }
    $key = $tmp['key'];
    $translations[$key] = $tmp;
    if (in_array($key, $array)) {
        $count = substr_count($key, '(*)');
        if ($count > 0) {
            foreach ($tmp as $k => $v) {
                if ($k == 'zh' || $k == 'key') {
                    continue;
                }
                if ($count != substr_count($v, '(*)')) {
                    $regrror[$key] = $tmp;
                    continue 2;
                }
            }
        }
    } else {
        $redundants[$key] = $tmp;
    }
}
foreach ($array as $text) {
    $key = trim($text);
    if (!isset($translations[$key])) {
        $needs[$key] = $key;
    }
}

if (count($needs) > 0) {
    $waits = array_chunk($needs, 100, true);
    foreach ($waits as $index => $items) {
        if ($index > 0) {
            print_r("\n");
        }
        print_r("正在翻译：" . count($items) . "/" . count($needs) . "\n");
        $content = implode("\n", $items);
        $open_ai = new OpenAi(OPEN_AI_KEY);
        $open_ai->setProxy(OPEN_AI_PROXY);
        $chat = $open_ai->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    "role" => "system",
                    "content" => <<<EOF
                        你是一个专业的翻译器，翻译的结果尽量符合“项目任务管理系统”的使用，将提供的文本按每行一个翻译成：
                        ```json
                        [
                            {
                                "key": "",
                                "zh": "",
                                "zh-CHT": "",
                                "en": "",
                                "ko": "",
                                "ja": "",
                                "de": "",
                                "fr": "",
                                "id": "",
                                "ru": ""
                            }
                        ]
                        ```
                        key：原文本，zh：留空(不用翻译)，zh-CHT：繁体中文，en：英语，ko：韩语，ja：日语，de：德语，fr：法语，id：印度尼西亚语，ru：俄语。
                        另外要注意的是其中的(*)为占位符，翻译时不要删除，也不要翻译这个占位符。请直接返回文本不需要使用markdown。
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
        $obj = json_decode($chat);
        $arr = json_decode($obj->choices[0]->message->content, true);
        if (!$arr || !is_array($arr)) {
            print_r("翻译失败：\n");
            print_r($content . "\n");
            continue;
        }

        foreach ($arr as $item) {
            foreach (['key', 'zh', 'zh-CHT', 'en', 'ko', 'ja', 'de', 'fr', 'id', 'ru'] as $lang) {
                if (!isset($item[$lang])) {
                    print_r("翻译结果不符合规范：{$item['key']}，缺少：{$lang}\n");
                    continue 2;
                }
            }
            if (empty($item['key'])) {
                print_r("翻译结果不符合规范：{$item['key']}，key为空\n");
                continue;
            }
            $count = substr_count($item['key'], '(*)');
            if ($count > 0) {
                foreach ($item as $k => $v) {
                    if ($k == 'zh' || $k == 'key') {
                        continue;
                    }
                    if ($count != substr_count($v, '(*)')) {
                        print_r("翻译结果不符合规范：{$item['key']}，正则匹配错误：{$k} => {$v}\n");
                        continue 2;
                    }
                }
            }
            $item['zh'] = "";
            $translations[$item['key']] = $item;
        }
        file_put_contents("translate.json", json_encode(array_values($translations), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        print_r("翻译完成：" . count($items) . "/" . count($needs) . "\n");
    }
}
