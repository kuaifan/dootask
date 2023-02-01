<?php

namespace App\Tasks;

use App\Module\Base;
use App\Module\Ihttp;
use Cache;
use Carbon\Carbon;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/**
 * 获取笑话、心灵鸡汤
 *
 * 在.env添加笑话 JUKE_KEY_JOKE
 * 在.env添加鸡汤 JUKE_KEY_SOUP
 *
 * 每日小时采集1次
 */
class JokeSoupTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        // 判断每小时执行一次
        if (Cache::get("JokeSoupTask:YmdH") == date("YmdH")) {
            return;
        }
        Cache::put("JokeSoupTask:YmdH", date("YmdH"), Carbon::now()->addDay());
        //
        $jokeKey = env("JUKE_KEY_JOKE");
        if ($jokeKey) {
            $array = Base::json2array(Cache::get("JokeSoupTask:jokes"));
            $res = Ihttp::ihttp_get("http://v.juhe.cn/joke/randJoke.php?key=" . $jokeKey);
            if (Base::isSuccess($res)) {
                $data = Base::json2array($res['data']);
                if ($data['reason'] === 'success') {
                    foreach ($data['result'] as $item) {
                        if ($text = trim($item['content'])) {
                            $array[] = $text;
                        }
                    }
                }
            }
            Cache::forever("JokeSoupTask:jokes", Base::array2json(array_slice($array, -100)));
        }
        //
        $soupKey = env("JUKE_KEY_SOUP");
        if ($soupKey) {
            $array = Base::json2array(Cache::get("JokeSoupTask:soups"));
            $res = Ihttp::ihttp_get("https://apis.juhe.cn/fapig/soup/query?key=" . $soupKey);
            if (Base::isSuccess($res)) {
                $data = Base::json2array($res['data']);
                if ($data['reason'] === 'success' && $text = trim($data['result']['text'])) {
                    $array[] = $text;
                }
            }
            Cache::forever("JokeSoupTask:soups", Base::array2json(array_slice($array, -24)));
        }
    }

    public function end()
    {

    }
}
