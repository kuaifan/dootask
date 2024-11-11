<?php

namespace App\Module\Table;

class OnlineData extends AbstractData
{
    public static function incr($userid)
    {
        $key = "online::" . $userid;
        $value = intval(self::get($key, 0));
        self::set($key, ++$value);
        return $value;
    }

    public static function decr($userid)
    {
        $key = "online::" . $userid;
        $value = intval(self::get($key, 0));
        self::set($key, --$value);
        return $value;
    }
}
