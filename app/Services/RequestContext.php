<?php

namespace App\Services;

use Illuminate\Http\Request;

class RequestContext
{
    /** @var array<string, array<string, mixed>> */
    private static array $context = [];

    private const REQUEST_ID_PREFIX = 'req_';

    /**
     * 生成请求唯一ID
     */
    public static function generateRequestId(): string
    {
        return self::REQUEST_ID_PREFIX . uniqid() . mt_rand(10000, 99999);
    }

    /**
     * 获取当前请求ID
     */
    private static function getCurrentRequestId(): ?string
    {
        /** @var Request $request */
        $request = request();
        return $request?->requestId;
    }

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
        $requestId = $requestId ?? self::getCurrentRequestId();
        if ($requestId === null) {
            return;
        }

        self::$context[$requestId] ??= [];
        self::$context[$requestId][$key] = $value;
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
        $requestId = $requestId ?? self::getCurrentRequestId();
        if ($requestId === null) {
            return $default;
        }

        return self::$context[$requestId][$key] ?? $default;
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
        $requestId = $requestId ?? self::getCurrentRequestId();
        if ($requestId === null) {
            return false;
        }

        return isset(self::$context[$requestId][$key]);
    }

    /**
     * 清理请求上下文
     *
     * @param string|null $requestId
     * @return void
     */
    public static function clear(?string $requestId = null): void
    {
        $requestId = $requestId ?? self::getCurrentRequestId();
        if ($requestId === null) {
            return;
        }

        unset(self::$context[$requestId]);
    }

    /**
     * 获取当前请求的所有上下文数据
     *
     * @param string|null $requestId
     * @return array<string, mixed>
     */
    public static function getAll(?string $requestId = null): array
    {
        $requestId = $requestId ?? self::getCurrentRequestId();
        if ($requestId === null) {
            return [];
        }

        return self::$context[$requestId] ?? [];
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
        $requestId = $requestId ?? self::getCurrentRequestId();
        if ($requestId === null) {
            return;
        }

        self::$context[$requestId] ??= [];
        self::$context[$requestId] = array_merge(self::$context[$requestId], $data);
    }
}
