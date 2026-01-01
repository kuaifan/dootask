<?php

namespace App\Module\Manticore;

use App\Module\Apps;
use App\Module\Doo;
use PDO;
use PDOException;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 基础类
 *
 * Manticore Search 兼容 MySQL 协议，可以直接使用 PDO 连接
 * 默认端口 9306 为 MySQL 协议端口
 */
class ManticoreBase
{
    private static ?PDO $pdo = null;
    private static bool $initialized = false;

    private string $host;
    private int $port;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->host = env('MANTICORE_HOST', 'manticore');
        $this->port = (int) env('MANTICORE_PORT', 9306);
    }

    /**
     * 获取 PDO 连接
     */
    private function getConnection(): ?PDO
    {
        if (!Apps::isInstalled("manticore")) {
            return null;
        }

        if (self::$pdo === null) {
            try {
                // Manticore 使用 MySQL 协议，不需要用户名密码
                $dsn = "mysql:host={$this->host};port={$this->port}";
                $pdo = new PDO($dsn, '', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 30,
                ]);

                // 初始化表结构
                if (!self::$initialized) {
                    $this->initializeTables($pdo);
                    self::$initialized = true;
                }

                self::$pdo = $pdo;
            } catch (PDOException $e) {
                Log::error('Manticore connection failed: ' . $e->getMessage());
                return null;
            }
        }

        return self::$pdo;
    }

    /**
     * 初始化表结构
     */
    private function initializeTables(PDO $pdo): void
    {
        try {
            // 创建文件向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS file_vectors (
                    id BIGINT,
                    file_id BIGINT,
                    userid BIGINT,
                    pshare BIGINT,
                    file_name TEXT,
                    file_type STRING,
                    file_ext STRING,
                    content TEXT,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='chinese' morphology='icu_chinese'
            ");

            // 创建键值存储表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS key_values (
                    id BIGINT,
                    k STRING,
                    v TEXT
                )
            ");

            // 创建文件用户关系表（用于权限过滤）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS file_users (
                    id BIGINT,
                    file_id BIGINT,
                    userid BIGINT,
                    permission INTEGER
                )
            ");

            // 创建用户向量表（联系人搜索）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_vectors (
                    id BIGINT,
                    userid BIGINT,
                    nickname TEXT,
                    email STRING,
                    tel STRING,
                    profession TEXT,
                    introduction TEXT,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='chinese' morphology='icu_chinese'
            ");

            // 创建项目向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS project_vectors (
                    id BIGINT,
                    project_id BIGINT,
                    userid BIGINT,
                    personal INTEGER,
                    project_name TEXT,
                    project_desc TEXT,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='chinese' morphology='icu_chinese'
            ");

            // 创建项目成员表（用于权限过滤）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS project_users (
                    id BIGINT,
                    project_id BIGINT,
                    userid BIGINT
                )
            ");

            // 创建任务向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS task_vectors (
                    id BIGINT,
                    task_id BIGINT,
                    project_id BIGINT,
                    userid BIGINT,
                    visibility INTEGER,
                    task_name TEXT,
                    task_desc TEXT,
                    task_content TEXT,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='chinese' morphology='icu_chinese'
            ");

            // 创建任务成员表（用于 visibility=2,3 的权限过滤）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS task_users (
                    id BIGINT,
                    task_id BIGINT,
                    userid BIGINT
                )
            ");

            Log::info('Manticore tables initialized successfully');
        } catch (PDOException $e) {
            Log::warning('Manticore initialization warning: ' . $e->getMessage());
            // 不抛出异常，表可能已存在
        }
    }

    /**
     * 重置连接（在长连接环境中使用）
     */
    public static function resetConnection(): void
    {
        self::$pdo = null;
        self::$initialized = false;
    }

    /**
     * 检查是否已安装
     */
    public static function isInstalled(): bool
    {
        return Apps::isInstalled("manticore");
    }

    /**
     * 执行 SQL（不返回结果）
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return bool 是否成功
     */
    public function execute(string $sql, array $params = []): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Log::error('Manticore execute error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return false;
        }
    }

    /**
     * 执行 SQL 并返回影响行数
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return int 影响行数，-1 表示失败
     */
    public function executeWithRowCount(string $sql, array $params = []): int
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return -1;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Log::error('Manticore execute error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return -1;
        }
    }

    /**
     * 查询并返回结果
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array 查询结果
     */
    public function query(string $sql, array $params = []): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Log::error('Manticore query error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return [];
        }
    }

    /**
     * 查询单行
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array|null 单行结果
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error('Manticore queryOne error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return null;
        }
    }

    /**
     * 转义 Manticore 全文搜索关键词
     *
     * @param string $keyword 原始关键词
     * @return string 转义后的关键词
     */
    public static function escapeMatch(string $keyword): string
    {
        // Manticore 特殊字符转义
        $special = ['\\', '(', ')', '|', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=', '<', '>', '*'];
        foreach ($special as $char) {
            $keyword = str_replace($char, '\\' . $char, $keyword);
        }
        return $keyword;
    }

    // ==============================
    // 文件向量相关方法
    // ==============================

    /**
     * 全文搜索文件
     *
     * @param string $keyword 关键词
     * @param int $userid 用户ID（0表示不限制权限）
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array 搜索结果
     */
    public static function fullTextSearch(string $keyword, int $userid = 0, int $limit = 20, int $offset = 0): array
    {
        if (empty($keyword)) {
            return [];
        }

        $instance = new self();
        $escapedKeyword = self::escapeMatch($keyword);

        if ($userid > 0) {
            // 带权限过滤的搜索
            // 先搜索文件，再通过应用层过滤权限
            $sql = "
                SELECT 
                    id,
                    file_id,
                    userid,
                    pshare,
                    file_name,
                    file_type,
                    file_ext,
                    SUBSTRING(content, 1, 500) as content_preview,
                    WEIGHT() as relevance
                FROM file_vectors
                WHERE MATCH('@(file_name,content) {$escapedKeyword}')
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $results = $instance->query($sql);

            // 应用层权限过滤：用户自己的文件 或 共享文件
            if (!empty($results)) {
                // 获取用户有权限的共享文件夹
                $shareFileIds = $instance->query(
                    "SELECT file_id FROM file_users WHERE userid IN (0, ?)",
                    [$userid]
                );
                $allowedShares = array_column($shareFileIds, 'file_id');

                $results = array_filter($results, function ($item) use ($userid, $allowedShares) {
                    // 自己的文件
                    if ($item['userid'] == $userid) {
                        return true;
                    }
                    // 共享文件（pshare 在允许的共享列表中）
                    if ($item['pshare'] > 0 && in_array($item['pshare'], $allowedShares)) {
                        return true;
                    }
                    return false;
                });
                $results = array_values($results);
            }

            return $results;
        } else {
            // 不限制权限
            $sql = "
                SELECT 
                    id,
                    file_id,
                    userid,
                    file_name,
                    file_type,
                    file_ext,
                    SUBSTRING(content, 1, 500) as content_preview,
                    WEIGHT() as relevance
                FROM file_vectors
                WHERE MATCH('@(file_name,content) {$escapedKeyword}')
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            return $instance->query($sql);
        }
    }

    /**
     * 向量相似度搜索
     *
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（0表示不限制权限）
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function vectorSearch(array $queryVector, int $userid = 0, int $limit = 20): array
    {
        if (empty($queryVector)) {
            return [];
        }

        $instance = new self();
        $vectorStr = '(' . implode(',', $queryVector) . ')';

        $sql = "
            SELECT 
                id,
                file_id,
                userid,
                pshare,
                file_name,
                file_type,
                file_ext,
                SUBSTRING(content, 1, 500) as content_preview,
                KNN_DIST() as distance
            FROM file_vectors
            WHERE KNN(content_vector, " . (int)$limit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        // 转换 distance 为 similarity（1 - distance 用于余弦距离）
        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // 权限过滤
        if ($userid > 0 && !empty($results)) {
            $shareFileIds = $instance->query(
                "SELECT file_id FROM file_users WHERE userid IN (0, ?)",
                [$userid]
            );
            $allowedShares = array_column($shareFileIds, 'file_id');

            $results = array_filter($results, function ($item) use ($userid, $allowedShares) {
                if ($item['userid'] == $userid) {
                    return true;
                }
                if ($item['pshare'] > 0 && in_array($item['pshare'], $allowedShares)) {
                    return true;
                }
                return false;
            });
            $results = array_values($results);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * 混合搜索（全文 + 向量，使用 RRF 融合）
     *
     * @param string $keyword 关键词
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（0表示不限制权限）
     * @param int $limit 返回数量
     * @param float $textWeight 全文搜索权重
     * @param float $vectorWeight 向量搜索权重
     * @return array 搜索结果
     */
    public static function hybridSearch(
        string $keyword,
        array $queryVector,
        int $userid = 0,
        int $limit = 20,
        float $textWeight = 0.5,
        float $vectorWeight = 0.5
    ): array {
        // 分别执行两种搜索
        $textResults = self::fullTextSearch($keyword, $userid, 50, 0);
        $vectorResults = !empty($queryVector)
            ? self::vectorSearch($queryVector, $userid, 50)
            : [];

        // 使用 RRF (Reciprocal Rank Fusion) 融合结果
        $scores = [];
        $items = [];
        $k = 60; // RRF 常数

        // 处理全文搜索结果
        foreach ($textResults as $rank => $item) {
            $fileId = $item['file_id'];
            $scores[$fileId] = ($scores[$fileId] ?? 0) + $textWeight / ($k + $rank + 1);
            $items[$fileId] = $item;
        }

        // 处理向量搜索结果
        foreach ($vectorResults as $rank => $item) {
            $fileId = $item['file_id'];
            $scores[$fileId] = ($scores[$fileId] ?? 0) + $vectorWeight / ($k + $rank + 1);
            if (!isset($items[$fileId])) {
                $items[$fileId] = $item;
            }
        }

        // 按融合分数排序
        arsort($scores);

        // 构建最终结果
        $results = [];
        $count = 0;
        foreach ($scores as $fileId => $score) {
            if ($count >= $limit) {
                break;
            }
            $item = $items[$fileId];
            $item['rrf_score'] = $score;
            $results[] = $item;
            $count++;
        }

        return $results;
    }

    /**
     * 插入或更新文件向量
     *
     * @param array $data 文件数据
     * @return bool 是否成功
     */
    public static function upsertFileVector(array $data): bool
    {
        $instance = new self();

        $fileId = $data['file_id'] ?? 0;
        if ($fileId <= 0) {
            return false;
        }

        // 先尝试删除已存在的记录
        $instance->execute("DELETE FROM file_vectors WHERE file_id = ?", [$fileId]);

        // 插入新记录
        $vectorValue = $data['content_vector'] ?? null;
        if ($vectorValue) {
            // 向量格式转换：从 [1,2,3] 转为 (1,2,3)
            $vectorValue = str_replace(['[', ']'], ['(', ')'], $vectorValue);

            $sql = "INSERT INTO file_vectors 
                    (id, file_id, userid, pshare, file_name, file_type, file_ext, content, content_vector)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $fileId,
                $fileId,
                $data['userid'] ?? 0,
                $data['pshare'] ?? 0,
                $data['file_name'] ?? '',
                $data['file_type'] ?? '',
                $data['file_ext'] ?? '',
                $data['content'] ?? '',
                $vectorValue
            ];
        } else {
            $sql = "INSERT INTO file_vectors 
                    (id, file_id, userid, pshare, file_name, file_type, file_ext, content)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $fileId,
                $fileId,
                $data['userid'] ?? 0,
                $data['pshare'] ?? 0,
                $data['file_name'] ?? '',
                $data['file_type'] ?? '',
                $data['file_ext'] ?? '',
                $data['content'] ?? ''
            ];
        }

        return $instance->execute($sql, $params);
    }

    /**
     * 删除文件向量
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function deleteFileVector(int $fileId): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM file_vectors WHERE file_id = ?", [$fileId]);
    }

    /**
     * 批量删除文件向量
     *
     * @param array $fileIds 文件ID列表
     * @return int 删除数量
     */
    public static function batchDeleteFileVectors(array $fileIds): int
    {
        if (empty($fileIds)) {
            return 0;
        }

        $instance = new self();
        $placeholders = implode(',', array_map('intval', $fileIds));

        return $instance->executeWithRowCount(
            "DELETE FROM file_vectors WHERE file_id IN ({$placeholders})"
        );
    }

    /**
     * 批量更新文件的 pshare 值
     *
     * @param array $fileIds 文件ID列表
     * @param int $pshare 新的 pshare 值
     * @return int 更新数量
     */
    public static function batchUpdatePshare(array $fileIds, int $pshare): int
    {
        if (empty($fileIds)) {
            return 0;
        }

        // Manticore 不支持批量 UPDATE，需要逐个更新
        $instance = new self();
        $count = 0;
        foreach ($fileIds as $fileId) {
            $result = $instance->execute(
                "UPDATE file_vectors SET pshare = ? WHERE file_id = ?",
                [$pshare, (int)$fileId]
            );
            if ($result) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 清空所有文件向量
     *
     * @return bool 是否成功
     */
    public static function clearAllFileVectors(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE file_vectors");
    }

    /**
     * 获取已索引的文件数量
     *
     * @return int 文件数量
     */
    public static function getIndexedFileCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM file_vectors");
        return $result ? (int) $result['cnt'] : 0;
    }

    /**
     * 获取最后索引的文件ID
     *
     * @return int 文件ID
     */
    public static function getLastIndexedFileId(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT MAX(file_id) as max_id FROM file_vectors");
        return $result ? (int) ($result['max_id'] ?? 0) : 0;
    }

    // ==============================
    // 文件用户关系方法
    // ==============================

    /**
     * 插入或更新文件用户关系
     *
     * @param int $fileId 文件ID
     * @param int $userid 用户ID（0表示公开）
     * @param int $permission 权限（0只读，1读写）
     * @return bool 是否成功
     */
    public static function upsertFileUser(int $fileId, int $userid, int $permission = 0): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $instance = new self();

        // 先删除已存在的记录
        $instance->execute(
            "DELETE FROM file_users WHERE file_id = ? AND userid = ?",
            [$fileId, $userid]
        );

        // 插入新记录
        $id = $fileId * 1000000 + $userid; // 生成唯一 ID
        return $instance->execute(
            "INSERT INTO file_users (id, file_id, userid, permission) VALUES (?, ?, ?, ?)",
            [$id, $fileId, $userid, $permission]
        );
    }

    /**
     * 批量同步文件用户关系（替换指定文件的所有关系）
     *
     * @param int $fileId 文件ID
     * @param array $users 用户列表 [['userid' => int, 'permission' => int], ...]
     * @return bool 是否成功
     */
    public static function syncFileUsers(int $fileId, array $users): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $instance = new self();

        try {
            // 删除旧关系
            $instance->execute("DELETE FROM file_users WHERE file_id = ?", [$fileId]);

            // 插入新关系
            foreach ($users as $user) {
                $userid = (int)($user['userid'] ?? 0);
                $permission = (int)($user['permission'] ?? 0);
                $id = $fileId * 1000000 + $userid;
                $instance->execute(
                    "INSERT INTO file_users (id, file_id, userid, permission) VALUES (?, ?, ?, ?)",
                    [$id, $fileId, $userid, $permission]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Manticore syncFileUsers error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除文件的所有用户关系
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function deleteFileUsers(int $fileId): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM file_users WHERE file_id = ?", [$fileId]);
    }

    /**
     * 删除指定文件和用户的关系
     *
     * @param int $fileId 文件ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function deleteFileUser(int $fileId, int $userid): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute(
            "DELETE FROM file_users WHERE file_id = ? AND userid = ?",
            [$fileId, $userid]
        );
    }

    /**
     * 获取文件用户关系数量
     *
     * @return int 关系数量
     */
    public static function getFileUserCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM file_users");
        return $result ? (int) $result['cnt'] : 0;
    }

    /**
     * 清空所有文件用户关系
     *
     * @return bool 是否成功
     */
    public static function clearAllFileUsers(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE file_users");
    }

    // ==============================
    // 用户向量方法（联系人搜索）
    // ==============================

    /**
     * 用户全文搜索
     *
     * @param string $keyword 关键词
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array 搜索结果
     */
    public static function userFullTextSearch(string $keyword, int $limit = 20, int $offset = 0): array
    {
        if (empty($keyword)) {
            return [];
        }

        $instance = new self();
        $escapedKeyword = self::escapeMatch($keyword);

        $sql = "
            SELECT 
                id,
                userid,
                nickname,
                email,
                tel,
                profession,
                SUBSTRING(introduction, 1, 200) as introduction_preview,
                WEIGHT() as relevance
            FROM user_vectors
            WHERE MATCH('@(nickname,email,profession,introduction) {$escapedKeyword}')
            ORDER BY relevance DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $instance->query($sql);
    }

    /**
     * 用户向量搜索
     *
     * @param array $queryVector 查询向量
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function userVectorSearch(array $queryVector, int $limit = 20): array
    {
        if (empty($queryVector)) {
            return [];
        }

        $instance = new self();
        $vectorStr = '(' . implode(',', $queryVector) . ')';

        $sql = "
            SELECT 
                id,
                userid,
                nickname,
                email,
                tel,
                profession,
                SUBSTRING(introduction, 1, 200) as introduction_preview,
                KNN_DIST() as distance
            FROM user_vectors
            WHERE KNN(content_vector, " . (int)$limit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        // 转换 distance 为 similarity
        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        return $results;
    }

    /**
     * 用户混合搜索
     *
     * @param string $keyword 关键词
     * @param array $queryVector 查询向量
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function userHybridSearch(string $keyword, array $queryVector, int $limit = 20): array
    {
        $textResults = self::userFullTextSearch($keyword, 50, 0);
        $vectorResults = !empty($queryVector) ? self::userVectorSearch($queryVector, 50) : [];

        // RRF 融合
        $scores = [];
        $items = [];
        $k = 60;

        foreach ($textResults as $rank => $item) {
            $id = $item['userid'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            $items[$id] = $item;
        }

        foreach ($vectorResults as $rank => $item) {
            $id = $item['userid'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            if (!isset($items[$id])) {
                $items[$id] = $item;
            }
        }

        arsort($scores);

        $results = [];
        $count = 0;
        foreach ($scores as $id => $score) {
            if ($count >= $limit) break;
            $item = $items[$id];
            $item['rrf_score'] = $score;
            $results[] = $item;
            $count++;
        }

        return $results;
    }

    /**
     * 插入或更新用户向量
     *
     * @param array $data 用户数据
     * @return bool 是否成功
     */
    public static function upsertUserVector(array $data): bool
    {
        $instance = new self();

        $userid = $data['userid'] ?? 0;
        if ($userid <= 0) {
            return false;
        }

        // 先删除已存在的记录
        $instance->execute("DELETE FROM user_vectors WHERE userid = ?", [$userid]);

        // 插入新记录
        $vectorValue = $data['content_vector'] ?? null;
        if ($vectorValue) {
            $vectorValue = str_replace(['[', ']'], ['(', ')'], $vectorValue);

            $sql = "INSERT INTO user_vectors 
                    (id, userid, nickname, email, tel, profession, introduction, content_vector)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $userid,
                $userid,
                $data['nickname'] ?? '',
                $data['email'] ?? '',
                $data['tel'] ?? '',
                $data['profession'] ?? '',
                $data['introduction'] ?? '',
                $vectorValue
            ];
        } else {
            $sql = "INSERT INTO user_vectors 
                    (id, userid, nickname, email, tel, profession, introduction)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $userid,
                $userid,
                $data['nickname'] ?? '',
                $data['email'] ?? '',
                $data['tel'] ?? '',
                $data['profession'] ?? '',
                $data['introduction'] ?? ''
            ];
        }

        return $instance->execute($sql, $params);
    }

    /**
     * 删除用户向量
     *
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function deleteUserVector(int $userid): bool
    {
        if ($userid <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM user_vectors WHERE userid = ?", [$userid]);
    }

    /**
     * 清空所有用户向量
     *
     * @return bool 是否成功
     */
    public static function clearAllUserVectors(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE user_vectors");
    }

    /**
     * 获取已索引的用户数量
     *
     * @return int 用户数量
     */
    public static function getIndexedUserCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM user_vectors");
        return $result ? (int) $result['cnt'] : 0;
    }

    // ==============================
    // 项目向量方法
    // ==============================

    /**
     * 项目全文搜索
     *
     * @param string $keyword 关键词
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array 搜索结果
     */
    public static function projectFullTextSearch(string $keyword, int $userid = 0, int $limit = 20, int $offset = 0): array
    {
        if (empty($keyword)) {
            return [];
        }

        $instance = new self();
        $escapedKeyword = self::escapeMatch($keyword);

        $sql = "
            SELECT 
                id,
                project_id,
                userid,
                personal,
                project_name,
                SUBSTRING(project_desc, 1, 300) as project_desc_preview,
                WEIGHT() as relevance
            FROM project_vectors
            WHERE MATCH('@(project_name,project_desc) {$escapedKeyword}')
            ORDER BY relevance DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $results = $instance->query($sql);

        // 权限过滤
        if ($userid > 0 && !empty($results)) {
            $memberProjects = $instance->query(
                "SELECT project_id FROM project_users WHERE userid = ?",
                [$userid]
            );
            $allowedProjects = array_column($memberProjects, 'project_id');

            $results = array_filter($results, function ($item) use ($allowedProjects) {
                return in_array($item['project_id'], $allowedProjects);
            });
            $results = array_values($results);
        }

        return $results;
    }

    /**
     * 项目向量搜索
     *
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function projectVectorSearch(array $queryVector, int $userid = 0, int $limit = 20): array
    {
        if (empty($queryVector)) {
            return [];
        }

        $instance = new self();
        $vectorStr = '(' . implode(',', $queryVector) . ')';

        $sql = "
            SELECT 
                id,
                project_id,
                userid,
                personal,
                project_name,
                SUBSTRING(project_desc, 1, 300) as project_desc_preview,
                KNN_DIST() as distance
            FROM project_vectors
            WHERE KNN(content_vector, " . (int)$limit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // 权限过滤
        if ($userid > 0 && !empty($results)) {
            $memberProjects = $instance->query(
                "SELECT project_id FROM project_users WHERE userid = ?",
                [$userid]
            );
            $allowedProjects = array_column($memberProjects, 'project_id');

            $results = array_filter($results, function ($item) use ($allowedProjects) {
                return in_array($item['project_id'], $allowedProjects);
            });
            $results = array_values($results);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * 项目混合搜索
     *
     * @param string $keyword 关键词
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function projectHybridSearch(string $keyword, array $queryVector, int $userid = 0, int $limit = 20): array
    {
        $textResults = self::projectFullTextSearch($keyword, $userid, 50, 0);
        $vectorResults = !empty($queryVector) ? self::projectVectorSearch($queryVector, $userid, 50) : [];

        $scores = [];
        $items = [];
        $k = 60;

        foreach ($textResults as $rank => $item) {
            $id = $item['project_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            $items[$id] = $item;
        }

        foreach ($vectorResults as $rank => $item) {
            $id = $item['project_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            if (!isset($items[$id])) {
                $items[$id] = $item;
            }
        }

        arsort($scores);

        $results = [];
        $count = 0;
        foreach ($scores as $id => $score) {
            if ($count >= $limit) break;
            $item = $items[$id];
            $item['rrf_score'] = $score;
            $results[] = $item;
            $count++;
        }

        return $results;
    }

    /**
     * 插入或更新项目向量
     *
     * @param array $data 项目数据
     * @return bool 是否成功
     */
    public static function upsertProjectVector(array $data): bool
    {
        $instance = new self();

        $projectId = $data['project_id'] ?? 0;
        if ($projectId <= 0) {
            return false;
        }

        // 先删除已存在的记录
        $instance->execute("DELETE FROM project_vectors WHERE project_id = ?", [$projectId]);

        // 插入新记录
        $vectorValue = $data['content_vector'] ?? null;
        if ($vectorValue) {
            $vectorValue = str_replace(['[', ']'], ['(', ')'], $vectorValue);

            $sql = "INSERT INTO project_vectors 
                    (id, project_id, userid, personal, project_name, project_desc, content_vector)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $projectId,
                $projectId,
                $data['userid'] ?? 0,
                $data['personal'] ?? 0,
                $data['project_name'] ?? '',
                $data['project_desc'] ?? '',
                $vectorValue
            ];
        } else {
            $sql = "INSERT INTO project_vectors 
                    (id, project_id, userid, personal, project_name, project_desc)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $params = [
                $projectId,
                $projectId,
                $data['userid'] ?? 0,
                $data['personal'] ?? 0,
                $data['project_name'] ?? '',
                $data['project_desc'] ?? ''
            ];
        }

        return $instance->execute($sql, $params);
    }

    /**
     * 删除项目向量
     *
     * @param int $projectId 项目ID
     * @return bool 是否成功
     */
    public static function deleteProjectVector(int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM project_vectors WHERE project_id = ?", [$projectId]);
    }

    /**
     * 清空所有项目向量
     *
     * @return bool 是否成功
     */
    public static function clearAllProjectVectors(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE project_vectors");
    }

    /**
     * 获取已索引的项目数量
     *
     * @return int 项目数量
     */
    public static function getIndexedProjectCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM project_vectors");
        return $result ? (int) $result['cnt'] : 0;
    }

    // ==============================
    // 项目成员关系方法
    // ==============================

    /**
     * 插入或更新项目成员关系
     *
     * @param int $projectId 项目ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function upsertProjectUser(int $projectId, int $userid): bool
    {
        if ($projectId <= 0 || $userid <= 0) {
            return false;
        }

        $instance = new self();

        // 先删除已存在的记录
        $instance->execute(
            "DELETE FROM project_users WHERE project_id = ? AND userid = ?",
            [$projectId, $userid]
        );

        // 插入新记录
        $id = $projectId * 1000000 + $userid;
        return $instance->execute(
            "INSERT INTO project_users (id, project_id, userid) VALUES (?, ?, ?)",
            [$id, $projectId, $userid]
        );
    }

    /**
     * 删除项目成员关系
     *
     * @param int $projectId 项目ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function deleteProjectUser(int $projectId, int $userid): bool
    {
        if ($projectId <= 0 || $userid <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute(
            "DELETE FROM project_users WHERE project_id = ? AND userid = ?",
            [$projectId, $userid]
        );
    }

    /**
     * 删除项目的所有成员关系
     *
     * @param int $projectId 项目ID
     * @return bool 是否成功
     */
    public static function deleteAllProjectUsers(int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM project_users WHERE project_id = ?", [$projectId]);
    }

    /**
     * 批量同步项目成员关系
     *
     * @param int $projectId 项目ID
     * @param array $userids 用户ID列表
     * @return bool 是否成功
     */
    public static function syncProjectUsers(int $projectId, array $userids): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $instance = new self();

        try {
            $instance->execute("DELETE FROM project_users WHERE project_id = ?", [$projectId]);

            foreach ($userids as $userid) {
                $id = $projectId * 1000000 + (int)$userid;
                $instance->execute(
                    "INSERT INTO project_users (id, project_id, userid) VALUES (?, ?, ?)",
                    [$id, $projectId, (int)$userid]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Manticore syncProjectUsers error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 清空所有项目成员关系
     *
     * @return bool 是否成功
     */
    public static function clearAllProjectUsers(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE project_users");
    }

    /**
     * 获取项目成员关系数量
     *
     * @return int 关系数量
     */
    public static function getProjectUserCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM project_users");
        return $result ? (int) $result['cnt'] : 0;
    }

    // ==============================
    // 任务向量方法
    // ==============================

    /**
     * 任务全文搜索
     *
     * @param string $keyword 关键词
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array 搜索结果
     */
    public static function taskFullTextSearch(string $keyword, int $userid = 0, int $limit = 20, int $offset = 0): array
    {
        if (empty($keyword)) {
            return [];
        }

        $instance = new self();
        $escapedKeyword = self::escapeMatch($keyword);

        $sql = "
            SELECT 
                id,
                task_id,
                project_id,
                userid,
                visibility,
                task_name,
                SUBSTRING(task_desc, 1, 300) as task_desc_preview,
                SUBSTRING(task_content, 1, 500) as task_content_preview,
                WEIGHT() as relevance
            FROM task_vectors
            WHERE MATCH('@(task_name,task_desc,task_content) {$escapedKeyword}')
            ORDER BY relevance DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $results = $instance->query($sql);

        // 权限过滤
        if ($userid > 0 && !empty($results)) {
            // 获取用户参与的项目
            $memberProjects = $instance->query(
                "SELECT project_id FROM project_users WHERE userid = ?",
                [$userid]
            );
            $allowedProjects = array_column($memberProjects, 'project_id');

            // 获取用户参与的任务
            $memberTasks = $instance->query(
                "SELECT task_id FROM task_users WHERE userid = ?",
                [$userid]
            );
            $allowedTasks = array_column($memberTasks, 'task_id');

            $results = array_filter($results, function ($item) use ($userid, $allowedProjects, $allowedTasks) {
                // 自己创建的任务
                if ($item['userid'] == $userid) {
                    return true;
                }
                // visibility=1 且是项目成员
                if ($item['visibility'] == 1 && in_array($item['project_id'], $allowedProjects)) {
                    return true;
                }
                // visibility=2,3 且是任务成员
                if (in_array($item['visibility'], [2, 3]) && in_array($item['task_id'], $allowedTasks)) {
                    return true;
                }
                return false;
            });
            $results = array_values($results);
        }

        return $results;
    }

    /**
     * 任务向量搜索
     *
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function taskVectorSearch(array $queryVector, int $userid = 0, int $limit = 20): array
    {
        if (empty($queryVector)) {
            return [];
        }

        $instance = new self();
        $vectorStr = '(' . implode(',', $queryVector) . ')';

        $sql = "
            SELECT 
                id,
                task_id,
                project_id,
                userid,
                visibility,
                task_name,
                SUBSTRING(task_desc, 1, 300) as task_desc_preview,
                SUBSTRING(task_content, 1, 500) as task_content_preview,
                KNN_DIST() as distance
            FROM task_vectors
            WHERE KNN(content_vector, " . (int)$limit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // 权限过滤
        if ($userid > 0 && !empty($results)) {
            $memberProjects = $instance->query(
                "SELECT project_id FROM project_users WHERE userid = ?",
                [$userid]
            );
            $allowedProjects = array_column($memberProjects, 'project_id');

            $memberTasks = $instance->query(
                "SELECT task_id FROM task_users WHERE userid = ?",
                [$userid]
            );
            $allowedTasks = array_column($memberTasks, 'task_id');

            $results = array_filter($results, function ($item) use ($userid, $allowedProjects, $allowedTasks) {
                if ($item['userid'] == $userid) {
                    return true;
                }
                if ($item['visibility'] == 1 && in_array($item['project_id'], $allowedProjects)) {
                    return true;
                }
                if (in_array($item['visibility'], [2, 3]) && in_array($item['task_id'], $allowedTasks)) {
                    return true;
                }
                return false;
            });
            $results = array_values($results);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * 任务混合搜索
     *
     * @param string $keyword 关键词
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function taskHybridSearch(string $keyword, array $queryVector, int $userid = 0, int $limit = 20): array
    {
        $textResults = self::taskFullTextSearch($keyword, $userid, 50, 0);
        $vectorResults = !empty($queryVector) ? self::taskVectorSearch($queryVector, $userid, 50) : [];

        $scores = [];
        $items = [];
        $k = 60;

        foreach ($textResults as $rank => $item) {
            $id = $item['task_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            $items[$id] = $item;
        }

        foreach ($vectorResults as $rank => $item) {
            $id = $item['task_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            if (!isset($items[$id])) {
                $items[$id] = $item;
            }
        }

        arsort($scores);

        $results = [];
        $count = 0;
        foreach ($scores as $id => $score) {
            if ($count >= $limit) break;
            $item = $items[$id];
            $item['rrf_score'] = $score;
            $results[] = $item;
            $count++;
        }

        return $results;
    }

    /**
     * 插入或更新任务向量
     *
     * @param array $data 任务数据
     * @return bool 是否成功
     */
    public static function upsertTaskVector(array $data): bool
    {
        $instance = new self();

        $taskId = $data['task_id'] ?? 0;
        if ($taskId <= 0) {
            return false;
        }

        // 先删除已存在的记录
        $instance->execute("DELETE FROM task_vectors WHERE task_id = ?", [$taskId]);

        // 插入新记录
        $vectorValue = $data['content_vector'] ?? null;
        if ($vectorValue) {
            $vectorValue = str_replace(['[', ']'], ['(', ')'], $vectorValue);

            $sql = "INSERT INTO task_vectors 
                    (id, task_id, project_id, userid, visibility, task_name, task_desc, task_content, content_vector)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $taskId,
                $taskId,
                $data['project_id'] ?? 0,
                $data['userid'] ?? 0,
                $data['visibility'] ?? 1,
                $data['task_name'] ?? '',
                $data['task_desc'] ?? '',
                $data['task_content'] ?? '',
                $vectorValue
            ];
        } else {
            $sql = "INSERT INTO task_vectors 
                    (id, task_id, project_id, userid, visibility, task_name, task_desc, task_content)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $taskId,
                $taskId,
                $data['project_id'] ?? 0,
                $data['userid'] ?? 0,
                $data['visibility'] ?? 1,
                $data['task_name'] ?? '',
                $data['task_desc'] ?? '',
                $data['task_content'] ?? ''
            ];
        }

        return $instance->execute($sql, $params);
    }

    /**
     * 更新任务可见性
     *
     * @param int $taskId 任务ID
     * @param int $visibility 可见性
     * @return bool 是否成功
     */
    public static function updateTaskVisibility(int $taskId, int $visibility): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute(
            "UPDATE task_vectors SET visibility = ? WHERE task_id = ?",
            [$visibility, $taskId]
        );
    }

    /**
     * 删除任务向量
     *
     * @param int $taskId 任务ID
     * @return bool 是否成功
     */
    public static function deleteTaskVector(int $taskId): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM task_vectors WHERE task_id = ?", [$taskId]);
    }

    /**
     * 清空所有任务向量
     *
     * @return bool 是否成功
     */
    public static function clearAllTaskVectors(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE task_vectors");
    }

    /**
     * 获取已索引的任务数量
     *
     * @return int 任务数量
     */
    public static function getIndexedTaskCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM task_vectors");
        return $result ? (int) $result['cnt'] : 0;
    }

    // ==============================
    // 任务成员关系方法
    // ==============================

    /**
     * 插入或更新任务成员关系
     *
     * @param int $taskId 任务ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function upsertTaskUser(int $taskId, int $userid): bool
    {
        if ($taskId <= 0 || $userid <= 0) {
            return false;
        }

        $instance = new self();

        // 先删除已存在的记录
        $instance->execute(
            "DELETE FROM task_users WHERE task_id = ? AND userid = ?",
            [$taskId, $userid]
        );

        // 插入新记录
        $id = $taskId * 1000000 + $userid;
        return $instance->execute(
            "INSERT INTO task_users (id, task_id, userid) VALUES (?, ?, ?)",
            [$id, $taskId, $userid]
        );
    }

    /**
     * 删除任务成员关系
     *
     * @param int $taskId 任务ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function deleteTaskUser(int $taskId, int $userid): bool
    {
        if ($taskId <= 0 || $userid <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute(
            "DELETE FROM task_users WHERE task_id = ? AND userid = ?",
            [$taskId, $userid]
        );
    }

    /**
     * 删除任务的所有成员关系
     *
     * @param int $taskId 任务ID
     * @return bool 是否成功
     */
    public static function deleteAllTaskUsers(int $taskId): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        $instance = new self();
        return $instance->execute("DELETE FROM task_users WHERE task_id = ?", [$taskId]);
    }

    /**
     * 批量同步任务成员关系
     *
     * @param int $taskId 任务ID
     * @param array $userids 用户ID列表
     * @return bool 是否成功
     */
    public static function syncTaskUsers(int $taskId, array $userids): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        $instance = new self();

        try {
            $instance->execute("DELETE FROM task_users WHERE task_id = ?", [$taskId]);

            foreach ($userids as $userid) {
                $id = $taskId * 1000000 + (int)$userid;
                $instance->execute(
                    "INSERT INTO task_users (id, task_id, userid) VALUES (?, ?, ?)",
                    [$id, $taskId, (int)$userid]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Manticore syncTaskUsers error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 清空所有任务成员关系
     *
     * @return bool 是否成功
     */
    public static function clearAllTaskUsers(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE task_users");
    }

    /**
     * 获取任务成员关系数量
     *
     * @return int 关系数量
     */
    public static function getTaskUserCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM task_users");
        return $result ? (int) $result['cnt'] : 0;
    }
}

