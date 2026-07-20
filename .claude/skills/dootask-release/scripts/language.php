<?php
// DooTask 发布——翻译流水线（纯本地 php，host 直接跑，不进容器、不调 OpenAI、不需 autoload）。
// 逐行对齐 language/translate.php 的检测/保存/生成逻辑，唯独把"调用外部模型翻译"那一段抽走，
// 翻译改在技能流程内完成。用 php 而非 node 的唯一原因：array_multisort + json_encode
// 的逐字节产物必须与项目原生工具一致，否则每次发版都会产生大面积排序/转义噪声 diff（已验证 host php 可字节级复现）。
//
// 子命令：
//   language.php diff
//       —— 输出 JSON：needs(待翻译，key 已转成 (%T1)/(%M1) 形式) / redundants(冗余,提示)
//          / formatErrors(raw 占位符、字段或编号错误,致命) / regexErrors(各语言占位符错乱,致命)
//   language.php apply <translated.json>
//       —— 把新翻译合并进 translate.json（追加 + 剔除冗余），不生成 public 文件
//   language.php generate
//       —— 由 translate.json 重新生成 public/language/{web,api}/*
//
// 项目根相对脚本自身定位（脚本固定在 <root>/.claude/skills/dootask-release/scripts/），与调用时的 cwd 无关。
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$ROOT = dirname(__DIR__, 4);
$LANG_DIR = $ROOT . '/language';
$LANG_FIELDS = ['key', 'zh', 'zh-CHT', 'en', 'ko', 'ja', 'de', 'fr', 'id', 'ru'];

if (!is_dir($LANG_DIR)) {
    fwrite(STDERR, "未找到 language 目录（$LANG_DIR）。\n");
    exit(1);
}
chdir($LANG_DIR);

$cmd = $argv[1] ?? '';

// ---- 公共：读取 original-*.txt ----
function read_generateds(): array
{
    $originals = [];
    $generateds = [];
    foreach (['web', 'api'] as $type) {
        $content = file_exists("original-{$type}.txt") ? file_get_contents("original-{$type}.txt") : "";
        $array = array_values(array_filter(array_unique(explode("\n", $content))));
        $generateds[$type] = $array;
        $originals = array_merge($originals, $array);
    }
    return [$originals, $generateds];
}

// ---- 公共：占位符与条目结构校验 ----
function parameter_tokens(string $value): array
{
    preg_match_all('/\(%[TM]\d+\)/', $value, $matches);
    return $matches[0];
}

function normalize_key(string $key): string
{
    return preg_replace(["/\(%T\d+\)/", "/\(%M\d+\)/"], ["(*)", "(**)"], $key);
}

function context_source_key(string $key): ?string
{
    if (preg_match('/^\[([a-z][a-z0-9_]*)]\.([\s\S]+)$/', $key, $matches)) {
        return $matches[2];
    }
    return null;
}

function validate_original_contexts(array $originals): array
{
    $errors = [];
    foreach ($originals as $index => $key) {
        if (preg_match('/^\[[^\]]+]\./', $key) && context_source_key($key) === null) {
            $errors[] = [
                'location' => "originals[index $index]",
                'message' => "上下文翻译键格式错误：$key",
            ];
        }
    }
    return $errors;
}

function validate_entry(array $obj, string $location, bool $requireTranslations): array
{
    $formatErrors = [];
    $regexErrors = [];
    foreach ($GLOBALS['LANG_FIELDS'] as $field) {
        if (!array_key_exists($field, $obj)) {
            $formatErrors[] = ['location' => "$location.$field", 'message' => "缺少字段 $field"];
        } elseif (!is_string($obj[$field])) {
            $formatErrors[] = ['location' => "$location.$field", 'message' => "字段 $field 必须是字符串"];
        } elseif ($requireTranslations && $field !== 'key' && $field !== 'zh' && $obj[$field] === '') {
            $formatErrors[] = ['location' => "$location.$field", 'message' => "字段 $field 不得为空"];
        }
    }
    if (!isset($obj['key']) || !is_string($obj['key'])) {
        return [$formatErrors, $regexErrors];
    }

    $key = $obj['key'];
    if (preg_match('/^\[[^\]]+]\./', $key)) {
        $sourceKey = context_source_key($key);
        if ($sourceKey === null) {
            $formatErrors[] = ['location' => "$location.key", 'message' => "上下文翻译键格式错误：$key"];
        } elseif (($obj['zh'] ?? null) !== $sourceKey) {
            $formatErrors[] = [
                'location' => "$location.zh",
                'message' => "上下文翻译键必须填写原文：$sourceKey",
            ];
        }
    }
    if (preg_match('/\(\*{1,2}\)/', $key)) {
        $formatErrors[] = [
            'location' => "$location.key",
            'message' => "key 不得包含 raw (*)/(**)，必须使用 (%T1)/(%M1)：$key",
        ];
    }
    $withoutValidParameters = preg_replace('/\(%[TM]\d+\)/', '', $key);
    if (str_contains($withoutValidParameters, '(%')) {
        $formatErrors[] = ['location' => "$location.key", 'message' => "存在非法参数占位符：$key"];
    }

    $keyTokens = parameter_tokens($key);
    foreach ($keyTokens as $index => $token) {
        preg_match('/\d+/', $token, $number);
        if ((int)$number[0] !== $index + 1) {
            $formatErrors[] = [
                'location' => "$location.key",
                'message' => "参数编号必须按出现顺序从 1 连续递增：$key",
            ];
            break;
        }
    }

    $expected = $keyTokens;
    sort($expected);
    foreach ($GLOBALS['LANG_FIELDS'] as $field) {
        if ($field === 'key' || !isset($obj[$field]) || !is_string($obj[$field]) || $obj[$field] === '') {
            continue;
        }
        $actual = parameter_tokens($obj[$field]);
        sort($actual);
        if ($actual !== $expected) {
            $regexErrors[] = [
                'location' => "$location.$field",
                'key' => $key,
                'field' => $field,
                'value' => $obj[$field],
                'expected' => $expected,
                'actual' => $actual,
                'message' => "参数占位符缺失、类型或编号不一致",
            ];
        }
    }
    return [$formatErrors, $regexErrors];
}

function print_validation_errors(array $formatErrors, array $regexErrors): void
{
    foreach ($formatErrors as $error) {
        fwrite(STDERR, "格式错误 {$error['location']}：{$error['message']}\n");
    }
    foreach ($regexErrors as $error) {
        fwrite(STDERR, "占位符错误 {$error['location']}：{$error['message']}；key={$error['key']}\n");
    }
}

// ---- 公共：构建 translations 映射（normalizedKey -> obj），并收集冗余/格式/占位符错乱 ----
function build_translations(array $originals): array
{
    $translations = [];
    $redundants = [];
    $regexErrors = [];
    $formatErrors = validate_original_contexts($originals);
    if (!file_exists("translate.json")) {
        fwrite(STDERR, "translate.json not exists\n");
        exit(1);
    }
    $tmps = json_decode(file_get_contents("translate.json"), true);
    if (!is_array($tmps)) {
        $formatErrors[] = ['location' => 'translate.json', 'message' => '根数据必须是 JSON 数组'];
        return [$translations, $redundants, $regexErrors, $formatErrors];
    }
    foreach ($tmps as $index => $obj) {
        $location = "translate.json[index $index]";
        if (!is_array($obj)) {
            $formatErrors[] = ['location' => $location, 'message' => '条目必须是对象'];
            continue;
        }
        [$entryFormatErrors, $entryRegexErrors] = validate_entry($obj, $location, false);
        $formatErrors = array_merge($formatErrors, $entryFormatErrors);
        $regexErrors = array_merge($regexErrors, $entryRegexErrors);
        if (!isset($obj['key']) || !is_string($obj['key'])) {
            continue;
        }
        $currentKey = $obj['key'];
        $originalKey = normalize_key($currentKey);
        if (!in_array($originalKey, $originals)) {
            $redundants[$originalKey] = $obj;
            continue;
        }
        if (isset($translations[$originalKey])) {
            $formatErrors[] = [
                'location' => $location,
                'message' => "规范化后 key 重复：$originalKey",
            ];
            continue;
        }
        $translations[$originalKey] = $obj;
    }
    return [$translations, $redundants, $regexErrors, $formatErrors];
}

// ---- 公共：由 translate.json + originals 重新生成 public 文件 ----
function generate(array $generateds, array $translations): void
{
    foreach ($generateds as $type => $array) {
        $datas = [];
        foreach ($array as $text) {
            $text = trim($text);
            if (isset($translations[$text])) {
                $datas[] = $translations[$text];
            }
        }
        $inOrder = [];
        foreach ($datas as $index => $item) {
            if (preg_match('/\(%[TM]\d+\)/', $item['key'])) {
                $inOrder[$index] = strlen($item['key']);
            } else {
                $inOrder[$index] = strlen($item['key']) + 10000000000;
            }
        }
        array_multisort($inOrder, SORT_DESC, $datas);
        $results = [];
        foreach ($datas as $items) {
            foreach ($items as $kk => $item) {
                $results[$kk][] = $item;
            }
        }
        if ($type === 'api') {
            if (!is_dir("../public/language/api")) {
                mkdir("../public/language/api", 0777, true);
            }
            foreach ($results as $kk => $item) {
                file_put_contents("../public/language/api/$kk.json", json_encode($item, JSON_UNESCAPED_UNICODE));
            }
        } elseif ($type === 'web') {
            if (!is_dir("../public/language/web")) {
                mkdir("../public/language/web", 0777, true);
            }
            foreach ($results as $kk => $item) {
                file_put_contents("../public/language/web/$kk.js", "if(typeof window.LANGUAGE_DATA===\"undefined\")window.LANGUAGE_DATA={};window.LANGUAGE_DATA[\"{$kk}\"]=" . json_encode($item, JSON_UNESCAPED_UNICODE));
            }
        }
        echo "[$type] total: " . count($results['key']) . "\n";
    }
}

if ($cmd === 'diff') {
    [$originals, $generateds] = read_generateds();
    [$translations, $redundants, $regexErrors, $formatErrors] = build_translations($originals);

    // 需要翻译的数据（对齐 translate.php 150-169：占位符按单一计数器编号）
    $needs = [];
    foreach ($originals as $text) {
        $key = trim($text);
        if ($key === '') {
            continue;
        }
        if (!isset($translations[$key])) {
            $needs[$key] = $key;
        }
    }
    $needsOut = [];
    foreach ($needs as $key) {
        $c = 1;
        $converted = preg_replace_callback('/\((\*+)\)/', function ($m) use (&$c) {
            $label = strlen($m[1]) > 1 ? "M" : "T";
            return "(%" . $label . $c++ . ")";
        }, $key);
        $need = ['key' => $converted];
        $contextSource = context_source_key($converted);
        if ($contextSource !== null) {
            $need['zh'] = $contextSource;
        }
        $needsOut[] = $need;
    }

    echo json_encode([
        'needsCount' => count($needsOut),
        'redundantCount' => count($redundants),
        'formatErrorCount' => count($formatErrors),
        'regexErrorCount' => count($regexErrors),
        'needs' => $needsOut,
        'redundants' => array_keys($redundants),
        'formatErrors' => array_values($formatErrors),
        'regexErrors' => array_values($regexErrors),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

    if (count($formatErrors) > 0 || count($regexErrors) > 0) {
        exit(2); // 已有数据格式或占位符错乱，需先修复
    }
    exit(0);
}

if ($cmd === 'apply') {
    $file = $argv[2] ?? '';
    if ($file === '' || !file_exists($file)) {
        fwrite(STDERR, "用法：apply <translated.json>（文件不存在）\n");
        exit(1);
    }
    [$originals, $generateds] = read_generateds();
    [$translations, $redundants, $regexErrors, $formatErrors] = build_translations($originals);
    if (count($formatErrors) > 0 || count($regexErrors) > 0) {
        print_validation_errors($formatErrors, $regexErrors);
        fwrite(STDERR, "translate.json 已有条目格式或占位符错误，请先修复再发版。\n");
        exit(2);
    }

    $incoming = json_decode(file_get_contents($file), true);
    if (!is_array($incoming)) {
        fwrite(STDERR, "translated.json 必须是数组\n");
        exit(1);
    }
    $originalSet = array_flip($originals);
    $incomingSeen = [];
    $itemsToAdd = [];
    foreach ($incoming as $index => $raw) {
        if (!is_array($raw)) {
            fwrite(STDERR, "新翻译 translated.json[index $index] 必须是对象\n");
            exit(1);
        }
        [$incomingFormatErrors, $incomingRegexErrors] = validate_entry($raw, "translated.json[index $index]", true);
        if (count($incomingFormatErrors) > 0 || count($incomingRegexErrors) > 0) {
            print_validation_errors($incomingFormatErrors, $incomingRegexErrors);
            exit(1);
        }
        // 规范化：固定字段顺序；上下文翻译键保留 zh 作为实际显示原文
        $item = [];
        foreach ($GLOBALS['LANG_FIELDS'] as $f) {
            $item[$f] = $f === 'zh' && context_source_key($raw['key']) === null ? '' : $raw[$f];
        }
        $originalKey = normalize_key($item['key']);
        if (!isset($originalSet[$originalKey])) {
            fwrite(STDERR, "新翻译 key 不在 original-web.txt/original-api.txt：{$item['key']}\n");
            exit(1);
        }
        if (isset($incomingSeen[$originalKey])) {
            fwrite(STDERR, "新翻译输入内部重复：translated.json[index {$incomingSeen[$originalKey]}] 与 translated.json[index $index] 规范化后均为「$originalKey」\n");
            exit(1);
        }
        if (isset($translations[$originalKey])) {
            fwrite(STDERR, "新翻译 key 已存在于 translate.json，非待补项或存在覆盖歧义：{$item['key']}（规范化：$originalKey）\n");
            exit(1);
        }
        $incomingSeen[$originalKey] = $index;
        $itemsToAdd[$originalKey] = $item;
    }

    foreach ($itemsToAdd as $originalKey => $item) {
        $translations[$originalKey] = $item;
    }

    // array_values：现有条目（去冗余）在前，新条目追加在后
    file_put_contents("translate.json", json_encode(array_values($translations), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode([
        'added' => count($itemsToAdd),
        'total' => count($translations),
        'droppedRedundant' => count($redundants),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

if ($cmd === 'generate') {
    [$originals, $generateds] = read_generateds();
    [$translations, $redundants, $regexErrors, $formatErrors] = build_translations($originals);
    if (count($formatErrors) > 0 || count($regexErrors) > 0) {
        print_validation_errors($formatErrors, $regexErrors);
        fwrite(STDERR, "translate.json 存在格式或占位符错误，已停止生成。\n");
        exit(2);
    }
    generate($generateds, $translations);
    exit(0);
}

fwrite(STDERR, "未知子命令：'$cmd'。可用：diff | apply <file> | generate\n");
exit(1);
