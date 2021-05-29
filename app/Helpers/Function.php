<?php

if (! function_exists('asset_main')) {
    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    function asset_main($path, $secure = null)
    {
        return preg_replace("/^https*:\/\//", "//", app('url')->asset($path, $secure));
    }
}
