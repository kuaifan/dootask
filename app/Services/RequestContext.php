<?php

namespace App\Services;

use App\Module\ClientContext;
use Illuminate\Http\Request;
use Swoole\Coroutine;

/**
 * 请求上下文
 */
class RequestContext
{
    /** @var string 请求ID的上下文键 */
    private const CONTEXT_KEY = 'request_id';

    /** @var string 请求ID前缀 */
    private const REQUEST_ID_PREFIX = 'req';

    /** @var int 上下文的TTL（生存时间） */
    private const TTL_SECONDS = 3600;  // 上下文 TTL 为 1 小时

    /** @var array<string, ClientContext> 存储每个请求的上下文数据 */
    private static array $context = [];

    /**
     * 生成请求唯一ID
     */
    public static function generateRequestId(): string
    {
        $pid = getmypid();
        $cid = Coroutine::getCid() ?? 0;
        $microtime = str_replace('.', '', microtime(true));
        return self::REQUEST_ID_PREFIX . '_' . $pid . '_' . $cid . '_' . $microtime . '_' . mt_rand(1000, 9999);
    }

    /**
     * 获取当前请求ID
     */
    public static function getCurrentRequestId($requestId = null): ?string
    {
        // 如果提供了有效的请求ID，直接返回
        if ($requestId && str_starts_with($requestId, self::REQUEST_ID_PREFIX)) {
            return $requestId;
        }

        // 尝试从当前请求获取
        $request = request();
        if ($request && method_exists($request, 'attributes') && $request->attributes) {
            if (!$request->attributes->has(static::CONTEXT_KEY)) {
                $request->attributes->set(static::CONTEXT_KEY, self::generateRequestId());
            }
            return $request->attributes->get(static::CONTEXT_KEY);
        }

        // 如果没有请求上下文，生成一个新的请求ID
        return self::generateRequestId();
    }

    /**
     * 获取当前请求的上下文示例
     */
    public static function getCurrentRequestContext($requestId = null): ?ClientContext
    {
        $requestId = self::getCurrentRequestId($requestId);
        if ($requestId === null) {
            return null;
        }

        if (!isset(self::$context[$requestId])) {
            // 如果上下文不存在，则创建一个新的上下文
            self::$context[$requestId] = new ClientContext();
        } else {
            // 如果上下文已存在，更新访问时间
            self::$context[$requestId]->update();
        }

        return self::$context[$requestId];
    }

    /**
     * 清理过期上下文数据，防止内存泄漏
     */
    public static function cleanExpired(): void
    {
        $now = microtime(true);

        // 清理过期的上下文
        foreach (self::$context as $requestId => $context) {
            if ($now - $context->updatedAt > self::TTL_SECONDS) {
                unset(self::$context[$requestId]);
            }
        }
    }

    /** ***************************************************************************************** */
    /** ***************************************************************************************** */
    /** ***************************************************************************************** */

    /**
     * 设置请求上下文
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $requestId
     * @return void
     */
    public static function set(string $key, mixed $value, ?string $requestId = null): void
    {
        $context = self::getCurrentRequestContext($requestId);
        if ($context === null) {
            return;
        }

        $context->set($key, $value);

        // 概率性清理，避免频繁清理影响性能
        if (mt_rand(1, 100) === 1) {
            self::cleanExpired();
        }
    }

    /**
     * 批量设置上下文数据
     *
     * @param array<string, mixed> $data
     * @param string|null $requestId
     * @return void
     */
    public static function setMultiple(array $data, ?string $requestId = null): void
    {
        $context = self::getCurrentRequestContext($requestId);
        if ($context === null) {
            return;
        }

        $context->setMultiple($data);
    }

    // 与 set 方法的区别是，save 方法会返回传入的 value 值
    public static function save(string $key, mixed $value, ?string $requestId = null): mixed
    {
        self::set($key, $value, $requestId);
        return $value;
    }

    /**
     * 获取请求上下文
     *
     * @param string $key
     * @param mixed $default
     * @param string|null $requestId
     * @return mixed
     */
    public static function get(string $key, mixed $default = null, ?string $requestId = null): mixed
    {
        $context = self::getCurrentRequestContext($requestId);
        if ($context === null) {
            return $default;
        }

        return $context->get($key, $default);
    }

    /**
     * 获取当前请求的所有上下文数据
     *
     * @param string|null $requestId
     * @return array<string, mixed>
     */
    public static function getAll(?string $requestId = null): array
    {
        $context = self::getCurrentRequestContext($requestId);
        if ($context === null) {
            return [];
        }

        return $context->context ?? [];
    }

    /**
     * 判断请求上下文是否存在
     *
     * @param string $key
     * @param string|null $requestId
     * @return bool
     */
    public static function has(string $key, ?string $requestId = null): bool
    {
        $context = self::getCurrentRequestContext($requestId);
        if ($context === null) {
            return false;
        }

        return $context->has($key);
    }

    /**
     * 清理请求上下文
     *
     * @param string|null $requestId
     * @return void
     */
    public static function clean(?string $requestId = null): void
    {
        $requestId = self::getCurrentRequestId($requestId);
        if ($requestId === null) {
            return;
        }

        unset(self::$context[$requestId]);
    }

    /** ***************************************************************************************** */
    /** ***************************************************************************************** */
    /** ***************************************************************************************** */

    /**
     * 更新请求的基本URL
     *
     * @param Request $request
     * @return void
     */
    public static function updateBaseUrl($request)
    {
        if ($request->path() !== 'api/system/setting') {
            return;
        }
        $schemeAndHttpHost = $request->getSchemeAndHttpHost();
        if (str_contains($schemeAndHttpHost, '127.0.0.1') || str_contains($schemeAndHttpHost, 'localhost')) {
            return;
        }
        \Cache::forever('RequestContext::base_url', $schemeAndHttpHost);
    }

    /**
     * 替换请求的基本URL
     *
     * @param string $url
     * @return string
     */
    public static function replaceBaseUrl(string $url): string
    {
        // 先提取主机部分
        $pattern = '/^(https?:\/\/[^\/?#:]+(:\d+)?)/i';
        if (!preg_match($pattern, $url, $matches)) {
            return $url; // 如果不是有效URL直接返回
        }

        $schemeAndHttpHost = $matches[1] ?? '';
        if (!$schemeAndHttpHost) {
            return $url;
        }

        // 只检查主机部分是否为本地主机
        if (str_contains($schemeAndHttpHost, '127.0.0.1') || str_contains($schemeAndHttpHost, 'localhost')) {
            $baseUrl = \Cache::get('RequestContext::base_url');
            if ($baseUrl) {
                return $baseUrl . substr($url, strlen($schemeAndHttpHost));
            }
        }

        return $url;
    }

    /**
     * 清除基本URL缓存
     */
    public static function clearBaseUrlCache(): void
    {
        \Cache::forget('RequestContext::base_url');
    }
}
