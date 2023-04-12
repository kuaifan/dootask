<?php

/**
 * 生成 /public/docs/swagger.json
 */

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$path = dirname(__FILE__). '/';
$files = controllerFiles($path);
//
$tags = [];
$paths = [];

foreach ($files as $fillPath) {
    $content = file_get_contents($fillPath);
    preg_match("/\/\*\*(\s+\*)+\s*@apiDefine\s+(.*?)\n(\s+\*)+(.*?)([\s\S]*?)\*\//i", $content, $matchs);
    if ($matchs) {
        $name = trim($matchs[2]);
        $description = trim($matchs[5]);
        $tags[$name] = $description;
    }
    preg_match_all("/\/\*\*(\s+\*)+\s*@api\s\{(get|post|put|delete|head|options|patch|propfind|proppatch|mkcol|copy|move|lock|unlock)\}([\s\S]*?)\*\//i", $content, $matchs);
    foreach ($matchs[2] as $key => $text) {
        $original = $matchs[0][$key];
        //
        preg_match("/\*\s*\@api\s+\{(get|post|put|delete|head|options|patch|propfind|proppatch|mkcol|copy|move|lock|unlock)\}\s+(.*?)\s+(.*?)\n/i", $original, $matchApi);
        preg_match("/\*\s*\@apiDescription\s+(.*?)\n/i", $original, $matchDesc);
        preg_match("/\*\s*\@apiGroup\s+(.*?)\n/i", $original, $matchGroup);
        if (empty($matchApi) || empty($matchGroup)) {
            continue;
        }
        $tagText = $tags[$matchGroup[1]] ?? $matchGroup[1];
        //
        preg_match_all("/\*\s*\@(apiHeader|apiBody|apiParam|apiQuery)\s+\{(.*?)\}(.*?)\n([\s\S]*?)(?=(\*\s*@|\*\s*\/))/i", $original, $matchParam);
        $paramArray = [];
        foreach ($matchParam[1] as $index => $value) {
            $paramItem = [];
            $in = '';
            if ($matchParam[1][$index] == 'apiHeader') {
                $in = 'header';
            } elseif ($matchParam[1][$index] == 'apiQuery') {
                $in = 'query';
            } elseif ($matchParam[1][$index] == 'apiBody') {
                $in = 'formData';
            } elseif ($matchParam[1][$index] == 'apiParam') {
                $schema = $matchParam[4][$index];
                $schema = preg_replace("/(\\n|^)\s*\*/", '', $schema);
                $schemaData = json5_decode($schema);
                if ($schemaData) {
                    $paramItem = [
                        "name" => "root",
                        "in" => "body",
                        "schema" => [
                            "\$schema" => "http://json-schema.org/draft-04/schema#",
                        ]
                    ];
                    $isArr = __isArr($schemaData);
                    $schemaData = $isArr ? $schemaData[1] : $schemaData;
                    $properties = __propertieData($schemaData);
                    if ($isArr) {
                        $paramItem["schema"]["type"] = "array";
                        $paramItem["schema"]["items"] = [
                            "type" => "object",
                            "properties" => $properties
                        ];
                    } else {
                        $paramItem["schema"]["type"] = "object";
                        $paramItem["schema"]["properties"] = $properties;
                    }
                } else {
                    $in = 'query';
                }
            } else {
                continue;
            }
            if ($in) {
                preg_match("/^\s*(\[*(.*?)\]*)\s+(.*?)$/i", $matchParam[3][$index], $matchParam3);
                if ($matchParam3) {
                    $type = strtolower($matchParam[2][$index]);
                    $name = $matchParam3[2] ?: $matchParam3[3];
                    $desc = trim($matchParam3[3]);
                    $matchParam4 = $matchParam[4][$index];
                    $matchParam4 = preg_replace("/(\\n|^)\s*\*/", "\n", $matchParam4);
                    if ($matchParam4) {
                        $matchParam4 = preg_replace("/(\\n|^)\s*-/", "$1", trim($matchParam4));
                        $desc = trim(sprintf("%s\n%s", $desc, $matchParam4));
                    }
                    $paramArray[] = [
                        "name" => $name,
                        "in" => $in,
                        "required" => !str_starts_with($matchParam3[1], '['),
                        "description" => $desc,
                        "type" => $type,
                    ];
                }
            }
            if ($paramItem) {
                $paramArray[] = $paramItem;
            }
        }
        //
        preg_match_all("/\*\s*\@apiSuccess\s+\{(.*?)\}(.*?)\n([\s\S]*?)(?=(\*\s*@|\*\s*\/))/i", $original, $matchSuccess);
        $successArray = [];
        $requiredArray = [];
        foreach ($matchSuccess[1] as $index => $value) {
            preg_match("/^\s*(\[*(.*?)\]*)\s+(.*?)$/i", $matchSuccess[2][$index], $matchSuccess2);
            $name = $matchSuccess2[2] ?: $matchSuccess2[3];
            if (!str_starts_with($matchSuccess2[1], '[')) {
                $requiredArray[] = $name;
            }
            $type = strtolower($matchSuccess[1][$index]);
            $successArray[$name] = [
                'type' => strtolower($matchSuccess[1][$index]),
                'description' => $matchSuccess2[3]
            ];
            if (in_array($type, ['object', 'array', 'json'])) {
                $exampleSuccess = $matchSuccess[3];
                preg_match("/\*\s*\@apiSuccessExample\s+\{(.*?)\}\s*{$name}:*([\s\S]*?)(?=(\*\s*@|\*\s*\/))/i", $original, $matchExample);
                if ($matchExample) {
                    $exampleSuccess = [$matchExample[2]];
                }
                $schemaData = [];
                if (array_filter($exampleSuccess, function ($item) use (&$schemaData) {
                    $item = preg_replace("/(\\n|^)\s*\*/", '', $item);
                    $itemData = json5_decode($item);
                    if ($itemData) {
                        $schemaData = $itemData;
                        return true;
                    }
                    return false;
                })) {
                    $isArr = __isArr($schemaData);
                    $schemaData = $isArr ? $schemaData[1] : $schemaData;
                    $successArray[$name]["properties"] = __propertieData($schemaData);
                }
            }
        }
        $responsesSchema = [
            "\$schema" => "http://json-schema.org/draft-04/schema#",
            'type' => 'object',
            'properties' => $successArray,
            'required' => $requiredArray,
        ];
        //
        $paths[$matchApi[2]][$matchApi[1]] = [
            'tags' => [$tagText],
            'consumes' => strtolower($matchApi[1]) == 'post' ? ['multipart/form-data'] : [],
            'summary' => $matchApi[3],
            'description' => $matchDesc ? trim($matchDesc[1]) : '',
            'parameters' => $paramArray,
            'responses' => [
                '200' => [
                    'description' => 'successful operation',
                    'schema' => $responsesSchema
                ]
            ]
        ];
    }
}
//
$tagArray = [];
foreach ($tags as $tag) {
    $tagArray[] = [
        'name' => $tag,
        'description' => $tag,
    ];
}
//
$title = "Api";
$version = "0.0.1";
$packagePath = dirname($path, 4) . '/package.json';
if (file_exists($packagePath)) {
    $packageArray = json_decode(file_get_contents(dirname($path, 4) . '/package.json'), true);
    if ($packageArray) {
        $title = $packageArray['name'];
        $version = $packageArray['version'];
    }
}
//
$content = [
    'swagger' => '2.0',
    'info' => [
        'title' => $title,
        'version' => $version,
    ],
    'basePath' => '/',
    'tags' => $tagArray,
    'schemes' => ['http'],
    'paths' => $paths
];

$filePath = dirname($path, 4) . '/public/docs';
mkdir($filePath, 0777, true);
file_put_contents($filePath . '/swagger.json', __array2json($content, true));

echo "Success \n";


/** ************************************************************** */
/** ************************************************************** */
/** ************************************************************** */

function __propertieData($jsonArray)
{
    list($jsonArray) = json5_value_note($jsonArray);
    $properties = [];
    foreach ($jsonArray as $k => $v) {
        $properties[$k] = [
            "type" => "string",
        ];
        list($value, $note) = json5_value_note($v);
        if ($note) {
            $properties[$k]["description"] = $note;
        }
        if (__isArr($value)) {
            $properties[$k]["type"] = "array";
            $properties[$k]["items"] = __propertieItem($value);
        } elseif (__isJson($value)) {
            $properties[$k]["type"] = "object";
            $properties[$k]["properties"] = __propertieItem($value);
        } elseif (floatval($value) == $value) {
            $properties[$k]["type"] = str_contains("$value", ".") ? "integer" : "number";
        }
    }
    return $properties;
}

function __propertieItem($data)
{
    if (__isArr($data)) {
        return [
            'type' => 'object',
            'properties' => $data ? __propertieData($data[0]) : json_decode('{}'),
        ];
    } elseif (__isJson($data)) {
        return $data ? __propertieData($data) : json_decode('{}');
    }
    return json_decode('{}');
}

function __array2json($array, $options = 0)
{
    if (!is_array($array)) {
        return $array;
    }
    if ($options === true) {
        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
    try {
        return json_encode($array, $options);
    } catch (Exception $e) {
        return '';
    }
}

function __isArr($data)
{
    return is_array($data) && str_starts_with(__array2json($data), '[');
}

function __isJson($data)
{
    return is_array($data) && str_starts_with(__array2json($data), '{');
}

/** ************************************************************** */
/** ************************************************************** */
/** ************************************************************** */

// JSON5 for PHP
// [LICENSE] MIT
// [URL] https://github.com/kujirahand/JSON5-PHP

function json5_value_note($v)
{
    if (is_array($v) && $v['type'] === '__note__') {
        return [$v['value'], $v['note']];
    }
    return [$v, null];
}

function json5_decode($json5, $assoc = true)
{
    return json5_value($json5, $assoc);
}

function json5_value(&$json5, $assoc)
{
    $json5 = trim($json5);
    json5_comment($json5); // skip comment
    // check 1char
    $c = substr($json5, 0, 1);
    // Object
    if ($c == '{') {
        return json5_note($json5, json5_object($json5, $assoc));
    }
    // Array
    if ($c == '[') {
        return json5_note($json5, json5_array($json5, $assoc));
    }
    // String
    if ($c == '"' || $c == "'") {
        return json5_note($json5, json5_string($json5));
    }
    // null / true / false / Infinity
    if (strncasecmp($json5, "null", 4) == 0) {
        $json5 = substr($json5, 4);
        return json5_note($json5, null);
    }
    if (strncasecmp($json5, "true", 4) == 0) {
        $json5 = substr($json5, 4);
        return json5_note($json5, true);
    }
    if (strncasecmp($json5, "false", 5) == 0) {
        $json5 = substr($json5, 5);
        return json5_note($json5, false);
    }
    if (strncasecmp($json5, "infinity", 8) == 0) {
        $json5 = substr($json5, 8);
        return json5_note($json5, INF);
    }
    // hex
    if (preg_match('/^(0x[a-zA-Z0-9]+)/', $json5, $m)) {
        $num = $m[1];
        $json5 = substr($json5, strlen($num));
        return json5_note($json5, intval($num, 16));
    }
    // number
    if (preg_match('/^((\+|\-)?\d*\.?\d*[eE]?(\+|\-)?\d*)/', $json5, $m)) {
        $num = $m[1];
        $json5 = substr($json5, strlen($num));
        return json5_note($json5, floatval($num));
    }
    // other char ... maybe error
    $json5 = substr($json5, 1);
    return json5_note($json5, null);
}

function json5_note($original, $value)
{
    $array = explode("\n", $original);
    preg_match("/\/\/(.*?)(\\n|$)/i", $array[0], $match);
    if ($match) {
        return [
            'type' => '__note__',
            'value' => $value,
            'note' => trim($match[1]),
        ];
    }
    return $value;
}

function json5_comment(&$json5)
{
    while ($json5 != '') {
        $json5 = ltrim($json5);
        $c2 = substr($json5, 0, 2);
        // block comment
        if ($c2 == '/*') {
            json5_token($json5, '*/');
            continue;
        }
        // line comment
        if ($c2 == '//') {
            json5_token($json5, "\n");
            continue;
        }
        break;
    }
}

function json5_string(&$json5)
{
    // check flag
    $flag = substr($json5, 0, 1);
    $json5 = substr($json5, 1);
    $str = "";
    while ($json5 != "") {
        // check
        $c = mb_substr($json5, 0, 1);
        $json5 = substr($json5, strlen($c));
        // Is terminator?
        if ($c == $flag) break;
        // escape
        if ($c == "\\") {
            if (str_starts_with($json5, "\r\n")) {
                $json5 = substr($json5, 2);
                $str .= "\r\n";
                continue;
            }
            if (str_starts_with($json5, "\n")) {
                $json5 = substr($json5, 1);
                $str .= "\n";
                continue;
            }
            if (substr($json5, 0, 1) == $flag) {
                $json5 = substr($json5, 1);
                $str .= "\\" . $flag;
                continue;
            }
        }
        $str .= $c;
    }
    $str = json_decode('"' . $str . '"'); // extract scape chars...
    return $str;
}

function json5_array(&$json5, $assoc)
{
    // skip '['
    $json5 = substr($json5, 1);
    $res = array();
    while ($json5 != '') {
        json5_comment($json5);
        // Array terminator?
        if (strncmp($json5, ']', 1) == 0) {
            $json5 = substr($json5, 1);
            break;
        }
        // get value
        $res[] = json5_value($json5, $assoc);
        // skip comma
        $json5 = ltrim($json5);
        if (str_starts_with($json5, ',')) {
            $json5 = substr($json5, 1);
        }
    }
    return $res;
}

function json5_object(&$json5, $assoc)
{
    // skip '{'
    $json5 = substr($json5, 1);
    if ($assoc) {
        $res = array();
    } else {
        $res = new \stdClass();
    }

    while ($json5 != '') {
        json5_comment($json5);
        // Object terminator?
        if (strncmp($json5, '}', 1) == 0) {
            $json5 = substr($json5, 1);
            break;
        }
        // get KEY
        $c = substr($json5, 0, 1);
        if ($c == '"' || $c == "'") {
            $key = json5_string($json5);
            json5_token($json5, ':');
        } else {
            $key = trim(json5_token($json5, ":"));
        }
        // get VALUE
        $value = json5_value($json5, $assoc);

        if ($assoc) {
            $res[$key] = $value;
        } else {
            $res->$key = $value;
        }

        // skip Comma
        $json5 = ltrim($json5);
        if (strncmp($json5, ',', 1) == 0) {
            $json5 = substr($json5, 1);
        }
    }
    return $res;
}

function json5_token(&$str, $spl)
{
    $i = strpos($str, $spl);
    if ($i === FALSE) {
        $result = $str;
        $str = "";
        return $result;
    }
    $result = substr($str, 0, $i);
    $str = substr($str, $i + strlen($spl));
    return $result;
}


/**
 * @param $path
 * @return array
 */
function controllerFiles($path)
{
    //判断目录是否为空
    if (!file_exists($path)) {
        return [];
    }
    $files = scandir($path);
    $fileItem = [];
    foreach ($files as $v) {
        $newPath = $path . DIRECTORY_SEPARATOR . $v;
        if (is_dir($newPath) && $v != '.' && $v != '..') {
            $fileItem = array_merge($fileItem, controllerFiles($newPath));
        } else if (is_file($newPath) && str_ends_with($newPath, 'Controller.php')) {
            $fileItem[] = $newPath;
        }
    }
    return $fileItem;
}
