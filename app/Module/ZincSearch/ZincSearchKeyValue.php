<?php

namespace App\Module\ZincSearch;

/**
 * ZincSearch 键值存储类
 *
 * 使用方法:
 *
 * 1. 基础方法
 *     - 确保索引存在: ensureIndex();
 *     - 清空所有数据: clear();
 *
 * 2. 基本操作
 *    - 设置键值: set('site_name', '我的网站');
 *    - 设置复杂数据: set('site_config', ['logo' => 'logo.png', 'theme' => 'dark']);
 *    - 合并现有数据: set('site_config', ['footer' => '版权所有'], true);
 *    - 获取键值: $siteName = get('site_name');
 *    - 获取键值带默认值: $theme = get('theme', 'light');
 *    - 删除键值: delete('temporary_data');
 *
 * 3. 批量操作
 *    - 批量设置: batchSet(['user_count' => 100, 'active_users' => 50]);
 *    - 批量获取: $stats = batchGet(['user_count', 'active_users']);
 */
class ZincSearchKeyValue
{
    /**
     * 索引名称
     */
    protected static string $indexName = 'keyValue';

    // ==============================
    // 基础方法
    // ==============================

    /**
     * 确保索引存在
     */
    public static function ensureIndex(): bool
    {
        if (!ZincSearchBase::indexExists(self::$indexName)) {
            $mappings = [
                'properties' => [
                    'key' => ['type' => 'keyword', 'index' => true],
                    'value' => ['type' => 'text', 'index' => true],
                    'created_at' => ['type' => 'date', 'index' => true],
                    'updated_at' => ['type' => 'date', 'index' => true]
                ]
            ];
            $result = ZincSearchBase::createIndex(self::$indexName, $mappings);
            return $result['success'] ?? false;
        }
        return true;
    }

    /**
     * 清空所有键值
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        // 检查索引是否存在
        if (!ZincSearchBase::indexExists(self::$indexName)) {
            return true;
        }

        // 删除再重建索引
        $deleteResult = ZincSearchBase::deleteIndex(self::$indexName);
        if (!($deleteResult['success'] ?? false)) {
            return false;
        }

        return self::ensureIndex();
    }

    // ==============================
    // 基本操作
    // ==============================

    /**
     * 设置键值
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param bool $merge 是否合并现有数据(如果值是数组)
     * @return bool 是否成功
     */
    public static function set(string $key, mixed $value, bool $merge = false): bool
    {
        if (!self::ensureIndex()) {
            return false;
        }

        // 检查键是否已存在
        if ($merge && is_array($value)) {
            $existingData = self::get($key);
            if (is_array($existingData)) {
                $value = array_merge($existingData, $value);
            }
        }

        // 检查是否存在相同键的文档 - 使用精确查询而不是普通搜索
        $searchParams = [
            'search_type' => 'term',
            'query' => [
                'field' => 'key',
                'term' => $key
            ],
            'from' => 0,
            'max_results' => 1
        ];

        $result = ZincSearchBase::advancedSearch(self::$indexName, $searchParams);
        $docs = $result['data']['hits']['hits'] ?? [];
        $now = date('c');

        if (!empty($docs)) {
            $docId = $docs[0]['_id'] ?? null;
            if ($docId) {
                // 更新现有文档
                $docData = [
                    'key' => $key,
                    'value' => $value,
                    'updated_at' => $now
                ];
                $updateResult = ZincSearchBase::updateDoc(self::$indexName, $docId, $docData);
                return $updateResult['success'] ?? false;
            }
        }

        // 创建新文档
        $docData = [
            'key' => $key,
            'value' => $value,
            'created_at' => $now,
            'updated_at' => $now
        ];
        $addResult = ZincSearchBase::addDoc(self::$indexName, $docData);
        return $addResult['success'] ?? false;
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
        if (!self::ensureIndex() || empty($key)) {
            return $default;
        }

        // 精确匹配键名
        $searchParams = [
            'search_type' => 'term',
            'query' => [
                'field' => 'key',
                'term' => $key
            ],
            'from' => 0,
            'max_results' => 1
        ];

        $result = ZincSearchBase::advancedSearch(self::$indexName, $searchParams);
        if (!($result['success'] ?? false)) {
            return $default;
        }

        $hits = $result['data']['hits']['hits'] ?? [];
        if (empty($hits)) {
            return $default;
        }

        return $hits[0]['_source']['value'] ?? $default;
    }

    /**
     * 删除键值
     *
     * @param string $key 键名
     * @return bool 是否成功
     */
    public static function delete(string $key): bool
    {
        if (!self::ensureIndex() || empty($key)) {
            return false;
        }

        // 查找文档ID
        $searchParams = [
            'search_type' => 'term',
            'query' => [
                'field' => 'key',
                'term' => $key
            ],
            'from' => 0,
            'max_results' => 1
        ];

        $result = ZincSearchBase::advancedSearch(self::$indexName, $searchParams);
        if (!($result['success'] ?? false)) {
            return false;
        }

        $hits = $result['data']['hits']['hits'] ?? [];
        if (empty($hits)) {
            return true; // 不存在视为删除成功
        }

        $docId = $hits[0]['_id'] ?? null;
        if (empty($docId)) {
            return false;
        }

        $deleteResult = ZincSearchBase::deleteDoc(self::$indexName, $docId);
        return $deleteResult['success'] ?? false;
    }

    // ==============================
    // 批量操作
    // ==============================

    /**
     * 批量设置键值对
     *
     * @param array $keyValues 键值对数组
     * @return bool 是否全部成功
     */
    public static function batchSet(array $keyValues): bool
    {
        if (!self::ensureIndex() || empty($keyValues)) {
            return false;
        }

        $docs = [];
        $now = date('c');

        foreach ($keyValues as $key => $value) {
            $docs[] = [
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        $result = ZincSearchBase::addDocs(self::$indexName, $docs);
        return $result['success'] ?? false;
    }

    /**
     * 批量获取键值
     *
     * @param array $keys 键名数组
     * @return array 键值对数组
     */
    public static function batchGet(array $keys): array
    {
        if (!self::ensureIndex() || empty($keys)) {
            return [];
        }

        $results = [];

        // 遍历查询每个键
        foreach ($keys as $key) {
            $results[$key] = self::get($key);
        }

        return $results;
    }
}
