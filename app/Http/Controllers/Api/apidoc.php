<?php

/**
 * 给apidoc项目增加顺序编号 / 支持恢复
 */

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

const NUMBER_WIDTH = 2;

$isRestore = isset($argv[1]) && strtolower($argv[1]) === 'restore';

$basePath = dirname(__FILE__) . '/';
$controllerFiles = glob($basePath . '*Controller.php');

if (!$controllerFiles) {
    echo "No Controller.php files found\n";
    exit(0);
}

foreach ($controllerFiles as $filePath) {
    $original = file_get_contents($filePath);
    [$updated, $linesChanged] = processFile($original, $isRestore);

    if (count($linesChanged) === 0) {
        continue;
    }

    file_put_contents($filePath, $updated);

    foreach ($linesChanged as $line) {
        echo $line . "\n";
    }
}

echo $isRestore ? "Restore Success \n" : "Success \n";

/**
 * 处理单个文件内容
 *
 * @param string $content
 * @param bool   $restore
 * @return array{string, array<int, string>}
 */
function processFile(string $content, bool $restore): array
{
    $lineChanges = [];
    $counter = 1;

    $pattern = '/\* @api \{([^\}]+)\}\s+([^\s]+)([^\r\n]*)(\r?\n)/';

    $updated = preg_replace_callback(
        $pattern,
        function (array $matches) use ($restore, &$counter, &$lineChanges) {
            $method = trim($matches[1]);
            if (!in_array(strtolower($method), ['get', 'post'], true)) {
                return $matches[0];
            }

            $endpoint = trim($matches[2]);
            $suffix = normalizeDescription(stripExistingNumbering($matches[3]));

            if (!$restore) {
                $numberedSuffix = formatNumber($counter) . '.';
                if ($suffix !== '') {
                    $numberedSuffix .= ' ' . $suffix;
                }
                $counter++;
            } else {
                $numberedSuffix = $suffix;
            }

            $newLine = renderAnnotation($method, $endpoint, $numberedSuffix);

            if ($newLine !== rtrim($matches[0], "\r\n")) {
                $lineChanges[] = $newLine;
            }

            return $newLine . $matches[4];
        },
        $content
    );

    if ($updated === null) {
        return [$content, []];
    }

    return [$updated, $lineChanges];
}

/**
 * 生成格式化后的注释行
 */
function renderAnnotation(string $method, string $endpoint, string $suffix = ''): string
{
    $line = "* @api {" . $method . "} " . $endpoint;

    if ($suffix !== '') {
        if ($suffix[0] !== ' ') {
            $line .= ' ';
        }
        $line .= $suffix;
    }

    return $line;
}

/**
 * 移除已有编号部分
 */
function stripExistingNumbering(string $text): string
{
    $trimmed = ltrim($text);
    $pattern = '/^\d+\.\s*/';
    return preg_replace($pattern, '', $trimmed) ?? $trimmed;
}

/**
 * 压缩多余空格
 */
function normalizeDescription(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    return preg_replace('/\s+/', ' ', $text) ?? $text;
}

/**
 * 生成固定宽度的数字
 */
function formatNumber(int $number): string
{
    return str_pad((string) $number, NUMBER_WIDTH, '0', STR_PAD_LEFT);
}
