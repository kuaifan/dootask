<?php

namespace App\Http\Middleware;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Module\Doo;
use App\Services\RequestContext;
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
        // 为每个请求生成唯一ID
        $request->requestId = RequestContext::generateRequestId();
        RequestContext::set('start_time', microtime(true));
        RequestContext::set('header_language', $request->header('language'));

        // 加载Doo类
        Doo::load();

        // 解密请求内容
        $encrypt = Doo::pgpParseStr($request->header('encrypt'));
        if ($request->isMethod('post')) {
            $version = $request->header('version');
            if ($version && version_compare($version, '0.25.48', '<')) {
                // 旧版本兼容 php://input
                parse_str($request->getContent(), $content);
                if ($content) {
                    $request->merge($content);
                }
            } elseif ($encrypt['encrypt_type'] === 'pgp' && $content = $request->input('encrypted')) {
                // 新版本解密提交的内容
                $content = Doo::pgpDecryptApi($content, $encrypt['encrypt_id']);
                if ($content) {
                    $request->merge($content);
                }
            }
        }

        // 强制 https
        $APP_SCHEME = env('APP_SCHEME', 'auto');
        if (in_array(strtolower($APP_SCHEME), ['https', 'on', 'ssl', '1', 'true', 'yes'], true)) {
            $request->setTrustedProxies([$request->getClientIp()], $request::HEADER_X_FORWARDED_PROTO);
        }

        // 执行下一个中间件
        $response = $next($request);

        // 加密返回内容
        if ($encrypt['client_type'] === 'pgp' && $content = $response->getContent()) {
            $content = Doo::pgpEncryptApi($content, $encrypt['client_key']);
            if ($content) {
                $response->setContent(json_encode(['encrypted' => $content]));
            }
        }

        // 返回响应
        return $response;
    }

    /**
     * @return void
     */
    public function terminate()
    {
        // 请求结束后清理上下文
        RequestContext::clear();
    }
}
