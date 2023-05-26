<?php

/**
 * 清除模型class注释
 */

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// 判断是否通过cmd命令执行的
if (function_exists('info')){
    echo "Success \n";
    return;
} 

$path = dirname(__FILE__). '/';
$lists = scandir($path);
//
foreach ($lists AS $item) {
    $fillPath = $path . $item;
    if (!in_array($item, ['AbstractModel.php', 'clearHelper.php']) && str_ends_with($item, '.php')) {
        $content = file_get_contents($fillPath);
        preg_match("/\/\*\*([\s\S]*?)class\s*(.*?)\s*extends\s*AbstractModel/i", $content, $matchs);
        if ($matchs[0]) {
            $content = str_replace($matchs[0], 'class ' . $matchs[2] . ' extends AbstractModel', $content);
            file_put_contents($fillPath, $content);
        }
    }
}
echo "Success \n";
