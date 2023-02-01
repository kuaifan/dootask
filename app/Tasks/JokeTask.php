<?php

namespace App\Tasks;

use App\Module\Base;
use App\Module\Ihttp;
use Cache;
use Carbon\Carbon;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/**
 * 获取笑话大全
 *
 * 每日小时采集1次
 */
class JokeTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        if (Cache::get("JokeTask:lastYmdH") == date("YmdH")) {
            return;
        }
        Cache::put("JokeTask:lastYmdH", date("YmdH"), Carbon::now()->addDay());
        //
        $array = [];
        for ($i = 0; $i < 10; $i++) {
            $res = Ihttp::ihttp_get("https://api.vvhan.com/api/joke?type=json");    // 备用 https://api.ghser.com/xiaohua?type=json
            if (Base::isError($res)) {
                return;
            }
            $data = Base::json2array($res['data']);
            if ($data['success'] !== true) {
                return;
            }
            $array[] = $data['joke'];
        }
        Cache::forever("JokeTask:rands", Base::array2json($array));
    }

    public function end()
    {

    }
}
