<?php

if (!function_exists('asset_main')) {
    function asset_main($path, $secure = null)
    {
        return preg_replace("/^https*:\/\//", "//", app('url')->asset($path, $secure));
    }
}

if (!function_exists('seeders_at')) {
    function seeders_at($data)
    {
        $diff = time() - strtotime("2021-07-02");
        $time = strtotime($data) + $diff;
        return date("Y-m-d H:i:s", $time);
    }
}
