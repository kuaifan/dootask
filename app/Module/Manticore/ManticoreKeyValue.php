<?php

namespace App\Module\Manticore;

use App\Module\Apps;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 键值存储类
 *
 * 用于存储同步进度等配置信息
 */
class ManticoreKeyValue
{
    /**
     * 获取值
     *
     * @param string $key 键
     * @param mixed $default 默认值
     * @return mixed 值
     */
    public static function get(string $key, $default = null)
    {
        if (!Apps::isInstalled("search")) {
            return $default;
        }

        $instance = new ManticoreBase();
        $result = $instance->queryOne(
            "SELECT v FROM key_values WHERE k = ?",
            [$key]
        );

        return $result ? $result['v'] : $default;
    }

    /**
     * 设置值
     *
     * @param string $key 键
     * @param mixed $value 值
     * @return bool 是否成功
     */
    public static function set(string $key, $value): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        $instance = new ManticoreBase();

        // 先删除已存在的记录
        $instance->execute("DELETE FROM key_values WHERE k = ?", [$key]);

        // 生成唯一 ID（基于 key 的 hash）
        $id = abs(crc32($key));

        // 插入新记录
        return $instance->execute(
            "INSERT INTO key_values (id, k, v) VALUES (?, ?, ?)",
            [$id, $key, (string)$value]
        );
    }

    /**
     * 删除值
     *
     * @param string $key 键
     * @return bool 是否成功
     */
    public static function delete(string $key): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        $instance = new ManticoreBase();
        return $instance->execute("DELETE FROM key_values WHERE k = ?", [$key]);
    }

    /**
     * 清空所有键值
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        $instance = new ManticoreBase();
        return $instance->execute("TRUNCATE TABLE key_values");
    }

    /**
     * 检查键是否存在
     *
     * @param string $key 键
     * @return bool 是否存在
     */
    public static function exists(string $key): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        $instance = new ManticoreBase();
        $result = $instance->queryOne(
            "SELECT id FROM key_values WHERE k = ?",
            [$key]
        );

        return $result !== null;
    }

    /**
     * 获取所有键值对
     *
     * @return array 键值对数组
     */
    public static function all(): array
    {
        if (!Apps::isInstalled("search")) {
            return [];
        }

        $instance = new ManticoreBase();
        $results = $instance->query("SELECT k, v FROM key_values");

        $data = [];
        foreach ($results as $row) {
            $data[$row['k']] = $row['v'];
        }

        return $data;
    }
}

