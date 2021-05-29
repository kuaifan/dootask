<?php

namespace App\Http\Middleware;

@error_reporting(E_ALL & ~E_NOTICE);

use Closure;

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

        return $next($request);
    }
}
