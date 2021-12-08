<?php

namespace App\Http\Middleware;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use Closure;
use Request;

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

        $APP_SCHEME = env('APP_SCHEME', 'auto');
        if (in_array(strtolower($APP_SCHEME), ['https', 'on', 'ssl', '1', 'true', 'yes'], true)) {
            $request->setTrustedProxies([$request->getClientIp()], $request::HEADER_X_FORWARDED_PROTO);
        }

        return $next($request);
    }
}
