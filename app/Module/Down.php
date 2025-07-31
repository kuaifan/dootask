<?php

namespace App\Module;

use Request;
use Cache;

class Down
{
    /**
     * @param $data
     * @param null $ttl
     * @return string
     */
    public static function cache_encode($data, $ttl = null): string
    {
        $base64 = base64_encode(Base::array2string($data));
        $key = md5($base64);
        Cache::put("down::{$key}", $base64, $ttl ?: now()->addHour());
        return $key;
    }

    /**
     * @param ?string $inputName
     * @return array
     */
    public static function cache_decode(?string $inputName = 'key'): array
    {
        $key = Request::input($inputName);
        $base64 = Cache::get("down::{$key}");
        if (empty($base64)) {
            return Base::ajaxError("请求已过期，请重新导出！", [], 0, 403);
        }
        //
        return Base::string2array(base64_decode($base64));
    }
}
