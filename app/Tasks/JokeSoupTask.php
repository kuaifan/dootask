<?php

namespace App\Tasks;

use App\Module\Base;
use App\Module\Extranet;
use Cache;
use Carbon\Carbon;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


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
        // 判断每分钟执行一次
        if (Cache::get(self::keyName("YmdHi")) == date("YmdHi")) {
            return;
        }
        Cache::put(self::keyName("YmdHi"), date("YmdHi"), Carbon::now()->addDay());
        //
        $array = Base::json2array(Cache::get(self::keyName("jokes")));
        $data = Extranet::randJoke();
        if ($data) {
            $array[] = $data;
        }
        Cache::forever(self::keyName("jokes"), Base::array2json(array_slice($array, -200)));
        //
        $array = Base::json2array(Cache::get(self::keyName("soups")));
        $data = Extranet::soups();
        if ($data) {
            $array[] = $data;
        }
        Cache::forever(self::keyName("soups"), Base::array2json(array_slice($array, -200)));
    }

    public function end()
    {

    }
}
