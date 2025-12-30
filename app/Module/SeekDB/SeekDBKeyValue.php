<?php

namespace App\Module\SeekDB;

use App\Module\Apps;
use Illuminate\Support\Facades\Log;

/**
 * SeekDB 键值存储类
 *
 * 用于存储同步进度、配置等键值数据
 *
 * 使用方法:
 *
 * 1. 基本操作
 *    - 设置键值: set('sync_last_id', 12345);
 *    - 获取键值: $lastId = get('sync_last_id', 0);
 *    - 删除键值: delete('sync_last_id');
 *
 * 2. 批量操作
 *    - 批量设置: batchSet(['key1' => 'value1', 'key2' => 'value2']);
 *    - 批量获取: $values = batchGet(['key1', 'key2']);
 */
class SeekDBKeyValue
{
    /**
     * 设置键值
     *
     * @param string $key 键名
     * @param mixed $value 值（会被 JSON 编码）
     * @return bool 是否成功
     */
    public static function set(string $key, mixed $value): bool
    {
        if (!Apps::isInstalled("seekdb") || empty($key)) {
            return false;
        }

        $instance = new SeekDBBase();
        $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        // 使用 REPLACE INTO 实现 upsert
        $sql = "REPLACE INTO key_values (k, v, updated_at) VALUES (?, ?, NOW())";

        return $instance->execute($sql, [$key, $jsonValue]);
    }

    /**
     * 获取键值
     *
     * @param string $key 键名
     * @param mixed $default 默认值
     * @return mixed 值或默认值
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!Apps::isInstalled("seekdb") || empty($key)) {
            return $default;
        }

        $instance = new SeekDBBase();
        $result = $instance->queryOne(
            "SELECT v FROM key_values WHERE k = ?",
            [$key]
        );

        if (!$result || !isset($result['v'])) {
            return $default;
        }

        $value = $result['v'];

        // 尝试 JSON 解码
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    /**
     * 删除键值
     *
     * @param string $key 键名
     * @return bool 是否成功
     */
    public static function delete(string $key): bool
    {
        if (!Apps::isInstalled("seekdb") || empty($key)) {
            return false;
        }

        $instance = new SeekDBBase();
        return $instance->execute(
            "DELETE FROM key_values WHERE k = ?",
            [$key]
        );
    }

    /**
     * 批量设置键值
     *
     * @param array $keyValues 键值对数组
     * @return bool 是否全部成功
     */
    public static function batchSet(array $keyValues): bool
    {
        if (!Apps::isInstalled("seekdb") || empty($keyValues)) {
            return false;
        }

        $instance = new SeekDBBase();
        $success = true;

        foreach ($keyValues as $key => $value) {
            $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            $result = $instance->execute(
                "REPLACE INTO key_values (k, v, updated_at) VALUES (?, ?, NOW())",
                [$key, $jsonValue]
            );
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 批量获取键值
     *
     * @param array $keys 键名数组
     * @return array 键值对数组
     */
    public static function batchGet(array $keys): array
    {
        if (!Apps::isInstalled("seekdb") || empty($keys)) {
            return [];
        }

        $instance = new SeekDBBase();
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $results = $instance->query(
            "SELECT k, v FROM key_values WHERE k IN ({$placeholders})",
            $keys
        );

        $values = [];
        foreach ($results as $row) {
            $value = $row['v'];
            $decoded = json_decode($value, true);
            $values[$row['k']] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }

        // 填充未找到的键为 null
        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                $values[$key] = null;
            }
        }

        return $values;
    }

    /**
     * 清空所有键值
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("seekdb")) {
            return false;
        }

        $instance = new SeekDBBase();
        return $instance->execute("TRUNCATE TABLE key_values");
    }
}

