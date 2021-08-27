<?php

namespace App\Http\Middleware;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use Closure;
use Request;
use URL;

class WebApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        global $_A;
        $_A = [];

        if (Request::input('__Access-Control-Allow-Origin') || Request::header('__Access-Control-Allow-Origin')) {
            header('Access-Control-Allow-Origin:*');
            header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers:Content-Type, platform, platform-channel, token, release, Access-Control-Allow-Origin');
        }

        $APP_FORCE_URL_SCHEME = env('APP_FORCE_URL_SCHEME', 'auto');
        if ($APP_FORCE_URL_SCHEME == 'https' || $APP_FORCE_URL_SCHEME === true) {
            URL::forceScheme('https');
        } elseif ($APP_FORCE_URL_SCHEME == 'http' || $APP_FORCE_URL_SCHEME === false) {
            URL::forceScheme('http');
        } elseif (Request::header('x-forwarded-server-port', 80) == 443) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
