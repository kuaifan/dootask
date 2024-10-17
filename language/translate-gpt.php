<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require __DIR__ . '/vendor/autoload.php';
use Orhanerday\OpenAi\OpenAi;

require_once ("config.php");


try {
    // 译文
    $translations = [];
    if (file_exists( "translate.json")) {
        $tmps = json_decode(file_get_contents("translate.json"), true);
        foreach ($tmps as $tmp) {
            if (!isset($tmp['key'])) {
                continue;
            }
            $translations[$tmp['key']] = $tmp;
        }
    }
    foreach (['api', 'web'] as $type) {
        // 读取文件
        $content = file_exists("original-{$type}.txt") ? file_get_contents("original-{$type}.txt") : "";
        $array = array_values(array_filter(array_unique(explode("\n", $content))));
        // 提取要翻译的
        $datas = [];
        $needs = [];
        foreach ($array as $text) {
            $text = trim($text);
            if ($tmp = json_decode($text, true)) {
                $key = key($tmp);
                $value = current($tmp);
            } else {
                $key = $value = $text;
            }
            if (isset($translations[$key])) {
                $datas[] = $translations[$key];
            } else {
                $needs[$key] = $value;
            }
        }
        $waits = array_chunk($needs, 200, true);
        // 分组翻译
        foreach ($waits as $items) {
            $content = implode("\n", $items);
            $open_ai = new OpenAi(OPEN_AI_KEY);
            $open_ai->setProxy(OPEN_AI_PROXY);

            $chat = $open_ai->chat([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        "role" => "user",
                        "content" => $content . '

------

帮我翻译以上内容，每行一个，按照下面的格式翻译成对应的语言，原内容放到key，zh留空，zh-CHT为繁体中文，en为英语，ko为韩语，ja为日语，de为德语，fr为法语，id为印度尼西亚语，ru为俄语。 另外要注意的是其中的(*)为占位符，翻译时不要删除，也不要翻译这个占位符。 请帮我一次性翻译完。

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
    },
]'
                    ],
                ],
                'temperature' => 1.0,
                'max_tokens' => 4000,
                'frequency_penalty' => 0,
                'presence_penalty' => 0,
            ]);

            $d = json_decode($chat);
            file_put_contents('translate-gpt.txt', $d->choices[0]->message->content . "\n", FILE_APPEND);
        }
    }

} catch (Exception $e) {
    print_r("[$type] error, " . $e->getMessage());
}

