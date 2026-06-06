<?php

namespace App\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * PHP（Swoole）只在内网被 nginx 访问，外部无法直连，故信任内网代理。
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * 只采信 X-Forwarded-Proto：nginx 已用 $the_scheme 覆盖该头（值由 nginx 控制），
     * 据此让 url() 实时跟随 https；host/for 一律不信，避免 Host 注入与 IP 伪造。
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_PROTO;
}
