<?php

namespace App\Module;

/**
 * 客户端上下文
 */
class ClientContext
{
    public array $context = [];
    public float $createdAt = 0;
    public float $updatedAt = 0;

    public function __construct()
    {
        $this->createdAt = microtime(true);
        $this->updatedAt = microtime(true);
    }

    /**
     * 设置上下文
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
        $this->updatedAt = microtime(true);
    }

    /**
     * 批量设置上下文
     * @param array $data
     * @return void
     */
    public function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->context[$key] = $value;
        }
        $this->updatedAt = microtime(true);
    }

    /**
     * 获取上下文
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * 判断上下文是否存在
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->context[$key]);
    }

    /**
     * 更新上下文
     * @return void
     */
    public function update(): void
    {
        $this->updatedAt = microtime(true);
    }

    /**
     * 清除上下文
     * @return void
     */
    public function clear(): void
    {
        $this->context = [];
    }
}
