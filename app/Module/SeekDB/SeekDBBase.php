<?php

namespace App\Module\SeekDB;

use App\Module\Apps;
use App\Module\Doo;
use PDO;
use PDOException;
use Illuminate\Support\Facades\Log;

/**
 * SeekDB 基础类
 *
 * SeekDB 兼容 MySQL 协议，可以直接使用 PDO 连接
 */
class SeekDBBase
{
    private static ?PDO $pdo = null;
    private static bool $initialized = false;

    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $database;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->host = env('SEEKDB_HOST', 'seekdb');
        $this->port = (int) env('SEEKDB_PORT', 2881);
        $this->user = env('SEEKDB_USER', 'root');
        $this->pass = env('SEEKDB_PASSWORD', '');
        $this->database = env('SEEKDB_DATABASE', 'dootask_search');
    }

    /**
     * 获取 PDO 连接
     */
    private function getConnection(): ?PDO
    {
        if (!Apps::isInstalled("seekdb")) {
            return null;
        }

        if (self::$pdo === null) {
            try {
                // 先连接不指定数据库，用于初始化
                $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
                $pdo = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 30,
                ]);

                // 初始化数据库和表
                if (!self::$initialized) {
                    $this->initializeDatabase($pdo);
                    self::$initialized = true;
                }

                // 切换到目标数据库
                $pdo->exec("USE `{$this->database}`");
                self::$pdo = $pdo;
            } catch (PDOException $e) {
                Log::error('SeekDB connection failed: ' . $e->getMessage());
                return null;
            }
        }

        return self::$pdo;
    }

    /**
     * 初始化数据库和表结构
     */
    private function initializeDatabase(PDO $pdo): void
    {
        try {
            // 创建数据库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->database}`");
            $pdo->exec("USE `{$this->database}`");

            // 创建文件向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS file_vectors (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    file_id BIGINT NOT NULL,
                    userid BIGINT NOT NULL,
                    pshare BIGINT NOT NULL DEFAULT 0,
                    file_name VARCHAR(500),
                    file_type VARCHAR(50),
                    file_ext VARCHAR(20),
                    content LONGTEXT,
                    content_vector VECTOR(1536),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_file_id (file_id),
                    KEY idx_userid (userid),
                    KEY idx_pshare (pshare),
                    FULLTEXT KEY ft_content (file_name, content)
                )
            ");

            // 创建键值存储表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS key_values (
                    k VARCHAR(255) PRIMARY KEY,
                    v TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // 创建文件用户关系表（用于权限过滤）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS file_users (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    file_id BIGINT NOT NULL,
                    userid BIGINT NOT NULL,
                    permission TINYINT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_file_user (file_id, userid),
                    KEY idx_userid (userid),
                    KEY idx_file_id (file_id)
                )
            ");

            Log::info('SeekDB database initialized successfully');
        } catch (PDOException $e) {
            Log::warning('SeekDB initialization warning: ' . $e->getMessage());
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
        return Apps::isInstalled("seekdb");
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
            Log::error('SeekDB execute error: ' . $e->getMessage(), [
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
            Log::error('SeekDB execute error: ' . $e->getMessage(), [
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
            Log::error('SeekDB query error: ' . $e->getMessage(), [
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
            Log::error('SeekDB queryOne error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return null;
        }
    }

    /**
     * 获取最后插入的 ID
     */
    public function lastInsertId(): ?int
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return null;
        }
    }

    // ==============================
    // 静态便捷方法
    // ==============================

    /**
     * 全文搜索
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
        $likeKeyword = "%{$keyword}%";

        // 构建 SQL - 同时搜索文件名和内容
        // 权限过滤通过 JOIN file_users 表实现
        if ($userid > 0) {
            // 用户可以看到：1) 自己的文件 2) 共享给自己或公开的文件
            // 注意：pshare 指向共享根文件夹的 ID，file_users 存储的是共享文件夹的权限关系
            $sql = "
                SELECT DISTINCT
                    fv.file_id,
                    fv.userid,
                    fv.file_name,
                    fv.file_type,
                    fv.file_ext,
                    SUBSTRING(fv.content, 1, 500) as content_preview,
                    (
                        CASE WHEN fv.file_name LIKE ? THEN 10 ELSE 0 END +
                        IFNULL(MATCH(fv.content) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                    ) AS relevance
                FROM file_vectors fv
                LEFT JOIN file_users fu ON fv.pshare = fu.file_id AND fv.pshare > 0
                WHERE (fv.file_name LIKE ? OR MATCH(fv.content) AGAINST(? IN NATURAL LANGUAGE MODE))
                  AND (fv.userid = ? OR fu.userid IN (0, ?))
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $params = [$likeKeyword, $keyword, $likeKeyword, $keyword, $userid, $userid];
        } else {
            // 不限制权限（管理员或后台）
            $sql = "
                SELECT 
                    file_id,
                    userid,
                    file_name,
                    file_type,
                    file_ext,
                    SUBSTRING(content, 1, 500) as content_preview,
                    (
                        CASE WHEN file_name LIKE ? THEN 10 ELSE 0 END +
                        IFNULL(MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                    ) AS relevance
                FROM file_vectors
                WHERE file_name LIKE ? OR MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $params = [$likeKeyword, $keyword, $likeKeyword, $keyword];
        }

        return $instance->query($sql, $params);
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
        $vectorStr = '[' . implode(',', $queryVector) . ']';

        if ($userid > 0) {
            // 权限过滤：pshare 指向共享根文件夹的 ID
            $sql = "
                SELECT DISTINCT
                    fv.file_id,
                    fv.userid,
                    fv.file_name,
                    fv.file_type,
                    fv.file_ext,
                    SUBSTRING(fv.content, 1, 500) as content_preview,
                    COSINE_SIMILARITY(fv.content_vector, ?) AS similarity
                FROM file_vectors fv
                LEFT JOIN file_users fu ON fv.pshare = fu.file_id AND fv.pshare > 0
                WHERE fv.content_vector IS NOT NULL
                  AND (fv.userid = ? OR fu.userid IN (0, ?))
                ORDER BY similarity DESC
                LIMIT " . (int)$limit;

            $params = [$vectorStr, $userid, $userid];
        } else {
            // 不限制权限
            $sql = "
                SELECT 
                    file_id,
                    userid,
                    file_name,
                    file_type,
                    file_ext,
                    SUBSTRING(content, 1, 500) as content_preview,
                    COSINE_SIMILARITY(content_vector, ?) AS similarity
                FROM file_vectors
                WHERE content_vector IS NOT NULL
                ORDER BY similarity DESC
                LIMIT " . (int)$limit;

            $params = [$vectorStr];
        }

        return $instance->query($sql, $params);
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
        // 分别执行两种搜索（权限过滤在各自方法内通过 JOIN 实现）
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

        // 检查是否存在
        $existing = $instance->queryOne(
            "SELECT id FROM file_vectors WHERE file_id = ?",
            [$fileId]
        );

        if ($existing) {
            // 更新
            $sql = "UPDATE file_vectors SET 
                    userid = ?,
                    pshare = ?,
                    file_name = ?,
                    file_type = ?,
                    file_ext = ?,
                    content = ?,
                    content_vector = ?,
                    updated_at = NOW()
                WHERE file_id = ?";

            $params = [
                $data['userid'] ?? 0,
                $data['pshare'] ?? 0,
                $data['file_name'] ?? '',
                $data['file_type'] ?? '',
                $data['file_ext'] ?? '',
                $data['content'] ?? '',
                $data['content_vector'] ?? null,
                $fileId
            ];
        } else {
            // 插入
            $sql = "INSERT INTO file_vectors 
                    (file_id, userid, pshare, file_name, file_type, file_ext, content, content_vector, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                $fileId,
                $data['userid'] ?? 0,
                $data['pshare'] ?? 0,
                $data['file_name'] ?? '',
                $data['file_type'] ?? '',
                $data['file_ext'] ?? '',
                $data['content'] ?? '',
                $data['content_vector'] ?? null
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
        return $instance->execute(
            "DELETE FROM file_vectors WHERE file_id = ?",
            [$fileId]
        );
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
        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));

        return $instance->executeWithRowCount(
            "DELETE FROM file_vectors WHERE file_id IN ({$placeholders})",
            $fileIds
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

        $instance = new self();
        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $params = array_merge([$pshare], $fileIds);

        return $instance->executeWithRowCount(
            "UPDATE file_vectors SET pshare = ?, updated_at = NOW() WHERE file_id IN ({$placeholders})",
            $params
        );
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

        // 检查是否存在
        $existing = $instance->queryOne(
            "SELECT id FROM file_users WHERE file_id = ? AND userid = ?",
            [$fileId, $userid]
        );

        if ($existing) {
            // 更新
            return $instance->execute(
                "UPDATE file_users SET permission = ?, updated_at = NOW() WHERE file_id = ? AND userid = ?",
                [$permission, $fileId, $userid]
            );
        } else {
            // 插入
            return $instance->execute(
                "INSERT INTO file_users (file_id, userid, permission) VALUES (?, ?, ?)",
                [$fileId, $userid, $permission]
            );
        }
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
                $instance->execute(
                    "INSERT INTO file_users (file_id, userid, permission) VALUES (?, ?, ?)",
                    [$fileId, $userid, $permission]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SeekDB syncFileUsers error: ' . $e->getMessage());
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
}

