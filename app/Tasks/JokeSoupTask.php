<?php

namespace App\Tasks;

use App\Module\AI;
use App\Module\Base;
use Cache;
use Carbon\Carbon;

/**
 * 获取笑话、心灵鸡汤
 *
 * 每分钟采集1次
 */
class JokeSoupTask extends AbstractTask
{
    public static function keyName($key)
    {
        return "JokeSoupTask-v2:{$key}";
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        // 判断每小时执行一次
        if (Cache::get(self::keyName("YmdH")) == date("YmdH")) {
            return;
        }
        Cache::put(self::keyName("YmdH"), date("YmdH"), Carbon::now()->addDay());

        // 开始生成笑话和心灵鸡汤
        $result = AI::generateJokeAndSoup();
        if (Base::isError($result)) {
            Cache::forget(self::keyName("YmdH"));
            return;
        }

        // 笑话和心灵鸡汤的缓存
        foreach (['jokes', 'soups'] as $key) {
            if ($result['data'][$key] && is_array($result['data'][$key])) {
                $array = Base::json2array(Cache::get(self::keyName($key)));
                $array = array_merge($array, $result['data'][$key]);
                Cache::forever(self::keyName($key), Base::array2json(array_slice($array, -200)));
            }
        }
    }

    public function end()
    {

    }
}
