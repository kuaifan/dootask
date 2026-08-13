<?php

namespace App\Http\Controllers;

use App\Models\WebDavOperationLog;
use App\Services\RequestContext;
use App\Services\WebDav\WebDavAuthenticator;
use App\Services\WebDav\WebDavConfig;
use App\Services\WebDav\WebDavServerFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sabre\HTTP\Request as SabreRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebDavProtocolController extends Controller
{
    public function __invoke(Request $request, string $path = ''): Response
    {
        $startedAt = microtime(true);
        RequestContext::set('start_time', $startedAt);
        $requestId = RequestContext::getCurrentRequestId();
        $user = null;
        $credential = null;
        $status = 500;
        $bytes = 0;

        try {
            $config = WebDavConfig::get();
            if (!$config['enabled']) {
                $status = 503;
                return response('WebDAV is disabled.', $status);
            }
            if (!$request->secure() && !app()->environment(['local', 'testing'])) {
                $status = 403;
                return response('HTTPS is required.', $status);
            }

            $authenticator = new WebDavAuthenticator();
            $auth = $authenticator->authenticate($request);
            if (!$auth) {
                $status = $authenticator->wasRateLimited() ? 429 : 401;
                $headers = $status === 401
                    ? ['WWW-Authenticate' => 'Basic realm="DooTask WebDAV", charset="UTF-8"']
                    : ['Retry-After' => '60'];
                return response('', $status, $headers);
            }
            [$user, $credential] = $auth;
            RequestContext::setMultiple([
                'webdav_user' => $user,
                'webdav_credential' => $credential,
            ]);

            $server = (new WebDavServerFactory())->make($user, $credential);
            $sabreRequest = new SabreRequest(
                $request->getMethod(),
                $request->getRequestUri(),
                $request->headers->all(),
                $request->getContent(true)
            );
            $sabreRequest->setAbsoluteUrl($request->getUri());
            $server->httpRequest = $sabreRequest;
            $server->start();

            $sabreResponse = $server->httpResponse;
            $status = $sabreResponse->getStatus();
            $bytes = intval($sabreResponse->getHeader('Content-Length') ?? 0);
            $response = $this->toSymfonyResponse($sabreResponse);
            $response->headers->set('X-Request-Id', $requestId);
            return $response;
        } finally {
            try {
                WebDavOperationLog::createInstance([
                    'request_id' => $requestId,
                    'userid' => $user?->userid,
                    'credential_id' => $credential?->id,
                    'method' => mb_substr($request->getMethod(), 0, 20),
                    'uri' => mb_substr('/dav/' . ltrim($path, '/'), 0, 1000),
                    'status' => $status,
                    'result' => $status >= 400 ? 'failed' : 'success',
                    'bytes' => $bytes,
                    'ip' => mb_substr((string) $request->ip(), 0, 45),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                    'duration_ms' => max(0, intval((microtime(true) - $startedAt) * 1000)),
                ])->save();
            } catch (\Throwable $exception) {
                Log::warning('WebDAV audit log failed', ['exception' => $exception->getMessage()]);
            }
            RequestContext::clean($requestId);
        }
    }

    private function toSymfonyResponse(\Sabre\HTTP\Response $sabreResponse): Response
    {
        $headers = [];
        foreach ($sabreResponse->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        $body = $sabreResponse->getBody();
        if (!is_resource($body) && !is_callable($body)) {
            return new Response(is_string($body) ? $body : '', $sabreResponse->getStatus(), $headers);
        }
        return new StreamedResponse(function () use ($body) {
            if (is_callable($body)) {
                $body();
                return;
            }
            $output = fopen('php://output', 'wb');
            stream_copy_to_stream($body, $output);
            fclose($output);
            if (is_resource($body)) fclose($body);
        }, $sabreResponse->getStatus(), $headers);
    }
}
