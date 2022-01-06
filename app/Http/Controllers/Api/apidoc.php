<?php

/**
 * 给apidoc项目增加顺序编号
 */

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$path = dirname(__FILE__). '/';
$lists = scandir($path);
//
foreach ($lists AS $item) {
    $fillPath = $path . $item;
    if (str_ends_with($fillPath, 'Controller.php')) {
        $content = file_get_contents($fillPath);
        preg_match_all("/\* @api \{(.+?)\} (.*?)\n/i", $content, $matchs);
        $i = 1;
        foreach ($matchs[2] AS $key=>$text) {
            if (in_array(strtolower($matchs[1][$key]), array('get', 'post'))) {
                $expl = explode(" ", __sRemove($text));
                $end = $expl[1];
                if ($expl[2]) {
                    $end = '';
                    foreach ($expl AS $k=>$v) { if ($k >= 2) { $end.= " ".$v; } }
                }
                $newtext = "* @api {".$matchs[1][$key]."} ".$expl[0]."          ".__zeroFill($i, 2).". ".trim($end);
                $content = str_replace("* @api {".$matchs[1][$key]."} ".$text, $newtext, $content);
                $i++;
                //
                echo $newtext;
                echo "\r\n";
            }
        }
        if ($i > 1) {
            file_put_contents($fillPath, $content);
        }
    }
}
echo "Success \n";

/** ************************************************************** */
/** ************************************************************** */
/** ************************************************************** */

/**
 * 替换所有空格
 * @param $str
 * @return mixed
 */
function __sRemove($str) {
    $str = str_replace("  ", " ", $str);
    if (__strExists($str, "  ")) {
        return __sRemove($str);
    }
    return $str;
}

/**
 * 是否包含字符
 * @param $string
 * @param $find
 * @return bool
 */
function __strExists($string, $find)
{
    return str_contains($string, $find);
}

/**
 * @param string $str 补零
 * @param int $length
 * @param int $after
 * @return bool|string
 */
function __zeroFill($str, $length = 0, $after = 1) {
    if (strlen($str) >= $length) {
        return $str;
    }
    $_str = '';
    for ($i = 0; $i < $length; $i++) {
        $_str .= '0';
    }
    if ($after) {
        $_ret = substr($_str . $str, $length * -1);
    } else {
        $_ret = substr($str . $_str, 0, $length);
    }
    return $_ret;
}
