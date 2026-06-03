<?php
// DooTask 发布——翻译流水线（纯本地 php，host 直接跑，不进容器、不调 OpenAI、不需 autoload）。
// 逐行对齐 language/translate.php 的检测/保存/生成逻辑，唯独把"调用外部模型翻译"那一段抽走，
// 翻译改在技能流程内完成。用 php 而非 node 的唯一原因：array_multisort + json_encode
// 的逐字节产物必须与项目原生工具一致，否则每次发版都会产生大面积排序/转义噪声 diff（已验证 host php 可字节级复现）。
//
// 子命令：
//   language.php diff
//       —— 输出 JSON：needs(待翻译，key 已转成 (%T1)/(%M1) 形式) / redundants(冗余,提示) / regexErrors(占位符错乱,致命)
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

// ---- 公共：构建 translations 映射（normalizedKey -> obj），并收集冗余/占位符错乱 ----
function build_translations(array $originals): array
{
    $translations = [];
    $redundants = [];
    $regrror = [];
    if (!file_exists("translate.json")) {
        fwrite(STDERR, "translate.json not exists\n");
        exit(1);
    }
    $tmps = json_decode(file_get_contents("translate.json"), true);
    foreach ($tmps as $obj) {
        if (!isset($obj['key'])) {
            continue;
        }
        $currentKey = $obj['key'];
        $originalKey = preg_replace(["/\(%T\d+\)/", "/\(%M\d+\)/"], ["(*)", "(**)"], $currentKey);
        if (!in_array($originalKey, $originals)) {
            $redundants[$originalKey] = $obj;
            continue;
        }
        $translations[$originalKey] = $obj;
        if (preg_match_all('/\(%[TM]\d+\)/', $currentKey, $matches)) {
            foreach ($matches[0] as $match) {
                foreach ($obj as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }
                    if (!str_contains($v, $match)) {
                        $regrror[$originalKey] = ['key' => $currentKey, 'field' => $k, 'value' => $v, 'match' => $match];
                        continue 2;
                    }
                }
            }
        }
    }
    return [$translations, $redundants, $regrror];
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
    [$translations, $redundants, $regrror] = build_translations($originals);

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
        $needsOut[] = ['key' => $converted];
    }

    echo json_encode([
        'needsCount' => count($needsOut),
        'redundantCount' => count($redundants),
        'regexErrorCount' => count($regrror),
        'needs' => $needsOut,
        'redundants' => array_keys($redundants),
        'regexErrors' => array_values($regrror),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

    if (count($regrror) > 0) {
        exit(2); // 已有数据占位符错乱，需先修复
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
    [$translations, $redundants, $regrror] = build_translations($originals);
    if (count($regrror) > 0) {
        fwrite(STDERR, "translate.json 已有条目占位符错乱，请先修复再发版。\n");
        exit(2);
    }

    $incoming = json_decode(file_get_contents($file), true);
    if (!is_array($incoming)) {
        fwrite(STDERR, "translated.json 必须是数组\n");
        exit(1);
    }
    $added = 0;
    foreach ($incoming as $raw) {
        foreach ($GLOBALS['LANG_FIELDS'] as $f) {
            if (!array_key_exists($f, $raw)) {
                fwrite(STDERR, "新翻译缺字段 \"$f\"：" . json_encode($raw, JSON_UNESCAPED_UNICODE) . "\n");
                exit(1);
            }
        }
        // 占位符完整性：key 里每个 (%T1)/(%M1) 必须出现在每个非空语言值里
        if (preg_match_all('/\(%[TM]\d+\)/', $raw['key'], $m)) {
            foreach ($m[0] as $match) {
                foreach ($GLOBALS['LANG_FIELDS'] as $f) {
                    if ($f === 'key' || $f === 'zh') {
                        continue;
                    }
                    if (empty($raw[$f])) {
                        continue;
                    }
                    if (!str_contains($raw[$f], $match)) {
                        fwrite(STDERR, "占位符 $match 在字段 \"$f\" 缺失：{$raw['key']}\n");
                        exit(1);
                    }
                }
            }
        }
        // 规范化：固定字段顺序 + zh 置空
        $item = [];
        foreach ($GLOBALS['LANG_FIELDS'] as $f) {
            $item[$f] = $f === 'zh' ? '' : $raw[$f];
        }
        $originalKey = preg_replace(["/\(%T\d+\)/", "/\(%M\d+\)/"], ["(*)", "(**)"], $item['key']);
        $translations[$originalKey] = $item;
        $added++;
    }

    // array_values：现有条目（去冗余）在前，新条目追加在后
    file_put_contents("translate.json", json_encode(array_values($translations), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode([
        'added' => $added,
        'total' => count($translations),
        'droppedRedundant' => count($redundants),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

if ($cmd === 'generate') {
    [$originals, $generateds] = read_generateds();
    [$translations] = build_translations($originals);
    generate($generateds, $translations);
    exit(0);
}

fwrite(STDERR, "未知子命令：'$cmd'。可用：diff | apply <file> | generate\n");
exit(1);
