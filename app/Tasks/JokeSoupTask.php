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
    private $keyPrefix = "JokeSoupTask-v2";

    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        // 判断每分钟执行一次
        if (Cache::get("{$this->keyPrefix}:YmdHi") == date("YmdHi")) {
            return;
        }
        Cache::put("{$this->keyPrefix}:YmdHi", date("YmdHi"), Carbon::now()->addDay());
        //
        $array = Base::json2array(Cache::get("{$this->keyPrefix}:jokes"));
        $data = Extranet::randJoke();
        if ($data) {
            $array[] = $data;
        }
        Cache::forever("{$this->keyPrefix}:jokes", Base::array2json(array_slice($array, -200)));
        //
        $array = Base::json2array(Cache::get("{$this->keyPrefix}:soups"));
        $data = Extranet::soups();
        if ($data) {
            $array[] = $data;
        }
        Cache::forever("{$this->keyPrefix}:soups", Base::array2json(array_slice($array, -200)));
    }

    public function end()
    {

    }
}
