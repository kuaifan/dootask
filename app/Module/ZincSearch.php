<?php

namespace App\Module;

/**
 * ZincSearch 公共类
 */
class ZincSearch
{
    private mixed $host;
    private mixed $port;
    private mixed $user;
    private mixed $pass;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->host = env('ZINCSEARCH_HOST', 'search');
        $this->port = env('ZINCSEARCH_PORT', '4080');
        $this->user = env('DB_USERNAME', '');
        $this->pass = env('DB_PASSWORD', '');
    }

    /**
     * 获取配置
     */
    private function config(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->pass
        ];
    }

    /**
     * 通用请求方法
     */
    private function request($path, $body = null, $method = 'POST')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://{$this->host}:{$this->port}{$path}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $headers = ['Content-Type: application/json'];
        if ($method === 'BULK') {
            $headers = ['Content-Type: text/plain'];
            $method = 'POST';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        $data = json_decode($result, true);
        return [
            'success' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => $data
        ];
    }

    // ==============================
    // 索引管理相关方法
    // ==============================

    /**
     * 创建索引
     */
    public static function createIndex($index, $mappings = []): array
    {
        $body = json_encode([
            'name' => $index,
            'mappings' => $mappings
        ]);
        return (new self())->request("/api/index", $body);
    }

    /**
     * 获取索引信息
     */
    public static function getIndex($index): array
    {
        return (new self())->request("/api/index/{$index}", null, 'GET');
    }

    /**
     * 获取所有索引
     */
    public static function listIndices(): array
    {
        return (new self())->request("/api/index", null, 'GET');
    }

    /**
     * 删除索引
     */
    public static function deleteIndex($index): array
    {
        return (new self())->request("/api/index/{$index}", null, 'DELETE');
    }

    /**
     * 分析文本
     */
    public static function analyze($analyzer, $text): array
    {
        $body = json_encode([
            'analyzer' => $analyzer,
            'text' => $text
        ]);
        return (new self())->request("/api/_analyze", $body);
    }

    // ==============================
    // 文档管理相关方法
    // ==============================

    /**
     * 写入单条文档
     */
    public static function addDoc($index, $doc): array
    {
        $body = json_encode($doc);
        return (new self())->request("/api/{$index}/_doc", $body);
    }

    /**
     * 更新文档
     */
    public static function updateDoc($index, $id, $doc): array
    {
        $body = json_encode($doc);
        return (new self())->request("/api/{$index}/_update/{$id}", $body);
    }

    /**
     * 删除文档
     */
    public static function deleteDoc($index, $id): array
    {
        return (new self())->request("/api/{$index}/_doc/{$id}", null, 'DELETE');
    }

    /**
     * 批量写入文档
     */
    public static function addDocs($index, $docs): array
    {
        $body = json_encode([
            'index' => $index,
            'records' => $docs
        ]);
        return (new self())->request("/api/_bulkv2", $body);
    }

    /**
     * 使用原始BULK API批量写入文档
     * 请求格式为Elasticsearch兼容格式
     */
    public static function bulkDocs($data): array
    {
        return (new self())->request("/api/_bulk", $data, 'BULK');
    }

    // ==============================
    // 搜索相关方法
    // ==============================

    /**
     * 查询文档
     */
    public static function search($index, $query, $from = 0, $size = 10): array
    {
        $searchParams = [
            'search_type' => 'match',
            'query' => [
                'term' => $query
            ],
            'from' => $from,
            'max_results' => $size
        ];

        $body = json_encode($searchParams);
        return (new self())->request("/api/{$index}/_search", $body);
    }

    /**
     * 高级查询文档
     */
    public static function advancedSearch($index, $searchParams): array
    {
        $body = json_encode($searchParams);
        return (new self())->request("/api/{$index}/_search", $body);
    }

    /**
     * 多索引查询
     */
    public static function multiSearch($queries): array
    {
        $body = json_encode($queries);
        return (new self())->request("/api/_msearch", $body);
    }
}
