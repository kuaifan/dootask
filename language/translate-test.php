<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


// 读取所有要翻译的内容
$array = [];
foreach (['api', 'web'] as $type) {
    $content = file_exists("original-{$type}.txt") ? file_get_contents("original-{$type}.txt") : "";
    $array = array_merge($array, array_values(array_filter(array_unique(explode("\n", $content)))));
}

// 判定是否存在translate.json文件
if (!file_exists( "translate.json")) {
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

if (count($regrror) > 0) {
    print_r("regrror:\n");
    print_r(implode("\n", array_keys($regrror)));
    print_r("\n\n");
}
if (count($redundants) > 0) {
    print_r("redundants:\n");
    print_r(implode("\n", array_keys($redundants)));
    print_r("\n\n");
}
if (count($needs) > 0) {
    print_r("needs:\n");
    print_r(implode("\n", array_keys($needs)));
    print_r("\n\n");
}

print_r([
    'translate_count' => count($translations),
    'regerror_count' => count($regrror),
    'redundant_count' => count($redundants),
    'need_count' => count($needs),
]);

