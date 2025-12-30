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

            // 创建用户向量表（联系人搜索）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_vectors (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    userid BIGINT NOT NULL,
                    nickname VARCHAR(200),
                    email VARCHAR(200),
                    tel VARCHAR(50),
                    profession VARCHAR(200),
                    introduction TEXT,
                    content_vector VECTOR(1536),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_userid (userid),
                    FULLTEXT KEY ft_content (nickname, email, profession, introduction)
                )
            ");

            // 创建项目向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS project_vectors (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    project_id BIGINT NOT NULL,
                    userid BIGINT NOT NULL,
                    personal TINYINT DEFAULT 0,
                    project_name VARCHAR(500),
                    project_desc TEXT,
                    content_vector VECTOR(1536),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_project_id (project_id),
                    KEY idx_userid (userid),
                    FULLTEXT KEY ft_content (project_name, project_desc)
                )
            ");

            // 创建项目成员表（用于权限过滤）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS project_users (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    project_id BIGINT NOT NULL,
                    userid BIGINT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_project_user (project_id, userid),
                    KEY idx_project_id (project_id),
                    KEY idx_userid (userid)
                )
            ");

            // 创建任务向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS task_vectors (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    task_id BIGINT NOT NULL,
                    project_id BIGINT NOT NULL,
                    userid BIGINT NOT NULL,
                    visibility TINYINT DEFAULT 1,
                    task_name VARCHAR(500),
                    task_desc TEXT,
                    task_content LONGTEXT,
                    content_vector VECTOR(1536),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_task_id (task_id),
                    KEY idx_project_id (project_id),
                    KEY idx_userid (userid),
                    KEY idx_visibility (visibility),
                    FULLTEXT KEY ft_content (task_name, task_desc, task_content)
                )
            ");

            // 创建任务成员表（用于 visibility=2,3 的权限过滤）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS task_users (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    task_id BIGINT NOT NULL,
                    userid BIGINT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY uk_task_user (task_id, userid),
                    KEY idx_task_id (task_id),
                    KEY idx_userid (userid)
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
        $likeKeyword = "%{$keyword}%";

        $sql = "
            SELECT 
                userid,
                nickname,
                email,
                tel,
                profession,
                SUBSTRING(introduction, 1, 200) as introduction_preview,
                (
                    CASE WHEN nickname LIKE ? THEN 10 ELSE 0 END +
                    CASE WHEN email LIKE ? THEN 5 ELSE 0 END +
                    IFNULL(MATCH(nickname, email, profession, introduction) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                ) AS relevance
            FROM user_vectors
            WHERE nickname LIKE ? OR email LIKE ? OR profession LIKE ?
               OR MATCH(nickname, email, profession, introduction) AGAINST(? IN NATURAL LANGUAGE MODE)
            ORDER BY relevance DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $params = [$likeKeyword, $likeKeyword, $keyword, $likeKeyword, $likeKeyword, $likeKeyword, $keyword];

        return $instance->query($sql, $params);
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
        $vectorStr = '[' . implode(',', $queryVector) . ']';

        $sql = "
            SELECT 
                userid,
                nickname,
                email,
                tel,
                profession,
                SUBSTRING(introduction, 1, 200) as introduction_preview,
                COSINE_SIMILARITY(content_vector, ?) AS similarity
            FROM user_vectors
            WHERE content_vector IS NOT NULL
            ORDER BY similarity DESC
            LIMIT " . (int)$limit;

        return $instance->query($sql, [$vectorStr]);
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

        $existing = $instance->queryOne("SELECT id FROM user_vectors WHERE userid = ?", [$userid]);

        if ($existing) {
            $sql = "UPDATE user_vectors SET 
                    nickname = ?,
                    email = ?,
                    tel = ?,
                    profession = ?,
                    introduction = ?,
                    content_vector = ?,
                    updated_at = NOW()
                WHERE userid = ?";

            $params = [
                $data['nickname'] ?? '',
                $data['email'] ?? '',
                $data['tel'] ?? '',
                $data['profession'] ?? '',
                $data['introduction'] ?? '',
                $data['content_vector'] ?? null,
                $userid
            ];
        } else {
            $sql = "INSERT INTO user_vectors 
                    (userid, nickname, email, tel, profession, introduction, content_vector, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                $userid,
                $data['nickname'] ?? '',
                $data['email'] ?? '',
                $data['tel'] ?? '',
                $data['profession'] ?? '',
                $data['introduction'] ?? '',
                $data['content_vector'] ?? null
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
        $likeKeyword = "%{$keyword}%";

        if ($userid > 0) {
            // 权限过滤：只搜索用户参与的项目
            $sql = "
                SELECT DISTINCT
                    pv.project_id,
                    pv.userid,
                    pv.personal,
                    pv.project_name,
                    SUBSTRING(pv.project_desc, 1, 300) as project_desc_preview,
                    (
                        CASE WHEN pv.project_name LIKE ? THEN 10 ELSE 0 END +
                        IFNULL(MATCH(pv.project_name, pv.project_desc) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                    ) AS relevance
                FROM project_vectors pv
                JOIN project_users pu ON pv.project_id = pu.project_id
                WHERE (pv.project_name LIKE ? OR MATCH(pv.project_name, pv.project_desc) AGAINST(? IN NATURAL LANGUAGE MODE))
                  AND pu.userid = ?
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $params = [$likeKeyword, $keyword, $likeKeyword, $keyword, $userid];
        } else {
            // 不限制权限
            $sql = "
                SELECT 
                    project_id,
                    userid,
                    personal,
                    project_name,
                    SUBSTRING(project_desc, 1, 300) as project_desc_preview,
                    (
                        CASE WHEN project_name LIKE ? THEN 10 ELSE 0 END +
                        IFNULL(MATCH(project_name, project_desc) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                    ) AS relevance
                FROM project_vectors
                WHERE project_name LIKE ? OR MATCH(project_name, project_desc) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $params = [$likeKeyword, $keyword, $likeKeyword, $keyword];
        }

        return $instance->query($sql, $params);
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
        $vectorStr = '[' . implode(',', $queryVector) . ']';

        if ($userid > 0) {
            $sql = "
                SELECT DISTINCT
                    pv.project_id,
                    pv.userid,
                    pv.personal,
                    pv.project_name,
                    SUBSTRING(pv.project_desc, 1, 300) as project_desc_preview,
                    COSINE_SIMILARITY(pv.content_vector, ?) AS similarity
                FROM project_vectors pv
                JOIN project_users pu ON pv.project_id = pu.project_id
                WHERE pv.content_vector IS NOT NULL AND pu.userid = ?
                ORDER BY similarity DESC
                LIMIT " . (int)$limit;

            $params = [$vectorStr, $userid];
        } else {
            $sql = "
                SELECT 
                    project_id,
                    userid,
                    personal,
                    project_name,
                    SUBSTRING(project_desc, 1, 300) as project_desc_preview,
                    COSINE_SIMILARITY(content_vector, ?) AS similarity
                FROM project_vectors
                WHERE content_vector IS NOT NULL
                ORDER BY similarity DESC
                LIMIT " . (int)$limit;

            $params = [$vectorStr];
        }

        return $instance->query($sql, $params);
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

        // RRF 融合
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

        $existing = $instance->queryOne("SELECT id FROM project_vectors WHERE project_id = ?", [$projectId]);

        if ($existing) {
            $sql = "UPDATE project_vectors SET 
                    userid = ?,
                    personal = ?,
                    project_name = ?,
                    project_desc = ?,
                    content_vector = ?,
                    updated_at = NOW()
                WHERE project_id = ?";

            $params = [
                $data['userid'] ?? 0,
                $data['personal'] ?? 0,
                $data['project_name'] ?? '',
                $data['project_desc'] ?? '',
                $data['content_vector'] ?? null,
                $projectId
            ];
        } else {
            $sql = "INSERT INTO project_vectors 
                    (project_id, userid, personal, project_name, project_desc, content_vector, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                $projectId,
                $data['userid'] ?? 0,
                $data['personal'] ?? 0,
                $data['project_name'] ?? '',
                $data['project_desc'] ?? '',
                $data['content_vector'] ?? null
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

        $existing = $instance->queryOne(
            "SELECT id FROM project_users WHERE project_id = ? AND userid = ?",
            [$projectId, $userid]
        );

        if ($existing) {
            return true; // 已存在
        }

        return $instance->execute(
            "INSERT INTO project_users (project_id, userid) VALUES (?, ?)",
            [$projectId, $userid]
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
                $instance->execute(
                    "INSERT INTO project_users (project_id, userid) VALUES (?, ?)",
                    [$projectId, (int)$userid]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SeekDB syncProjectUsers error: ' . $e->getMessage());
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
        $likeKeyword = "%{$keyword}%";

        if ($userid > 0) {
            // 复杂权限过滤：
            // 1. 自己创建的任务
            // 2. visibility=1 且是项目成员
            // 3. visibility=2,3 且是任务成员
            $sql = "
                SELECT DISTINCT
                    tv.task_id,
                    tv.project_id,
                    tv.userid,
                    tv.visibility,
                    tv.task_name,
                    SUBSTRING(tv.task_desc, 1, 300) as task_desc_preview,
                    SUBSTRING(tv.task_content, 1, 500) as task_content_preview,
                    (
                        CASE WHEN tv.task_name LIKE ? THEN 10 ELSE 0 END +
                        IFNULL(MATCH(tv.task_name, tv.task_desc, tv.task_content) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                    ) AS relevance
                FROM task_vectors tv
                LEFT JOIN project_users pu ON tv.project_id = pu.project_id AND pu.userid = ?
                LEFT JOIN task_users tu ON tv.task_id = tu.task_id AND tu.userid = ?
                WHERE (tv.task_name LIKE ? OR MATCH(tv.task_name, tv.task_desc, tv.task_content) AGAINST(? IN NATURAL LANGUAGE MODE))
                  AND (
                    tv.userid = ?
                    OR (tv.visibility = 1 AND pu.userid IS NOT NULL)
                    OR (tv.visibility IN (2, 3) AND tu.userid IS NOT NULL)
                  )
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $params = [$likeKeyword, $keyword, $userid, $userid, $likeKeyword, $keyword, $userid];
        } else {
            // 不限制权限
            $sql = "
                SELECT 
                    task_id,
                    project_id,
                    userid,
                    visibility,
                    task_name,
                    SUBSTRING(task_desc, 1, 300) as task_desc_preview,
                    SUBSTRING(task_content, 1, 500) as task_content_preview,
                    (
                        CASE WHEN task_name LIKE ? THEN 10 ELSE 0 END +
                        IFNULL(MATCH(task_name, task_desc, task_content) AGAINST(? IN NATURAL LANGUAGE MODE), 0)
                    ) AS relevance
                FROM task_vectors
                WHERE task_name LIKE ? OR MATCH(task_name, task_desc, task_content) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $params = [$likeKeyword, $keyword, $likeKeyword, $keyword];
        }

        return $instance->query($sql, $params);
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
        $vectorStr = '[' . implode(',', $queryVector) . ']';

        if ($userid > 0) {
            $sql = "
                SELECT DISTINCT
                    tv.task_id,
                    tv.project_id,
                    tv.userid,
                    tv.visibility,
                    tv.task_name,
                    SUBSTRING(tv.task_desc, 1, 300) as task_desc_preview,
                    SUBSTRING(tv.task_content, 1, 500) as task_content_preview,
                    COSINE_SIMILARITY(tv.content_vector, ?) AS similarity
                FROM task_vectors tv
                LEFT JOIN project_users pu ON tv.project_id = pu.project_id AND pu.userid = ?
                LEFT JOIN task_users tu ON tv.task_id = tu.task_id AND tu.userid = ?
                WHERE tv.content_vector IS NOT NULL
                  AND (
                    tv.userid = ?
                    OR (tv.visibility = 1 AND pu.userid IS NOT NULL)
                    OR (tv.visibility IN (2, 3) AND tu.userid IS NOT NULL)
                  )
                ORDER BY similarity DESC
                LIMIT " . (int)$limit;

            $params = [$vectorStr, $userid, $userid, $userid];
        } else {
            $sql = "
                SELECT 
                    task_id,
                    project_id,
                    userid,
                    visibility,
                    task_name,
                    SUBSTRING(task_desc, 1, 300) as task_desc_preview,
                    SUBSTRING(task_content, 1, 500) as task_content_preview,
                    COSINE_SIMILARITY(content_vector, ?) AS similarity
                FROM task_vectors
                WHERE content_vector IS NOT NULL
                ORDER BY similarity DESC
                LIMIT " . (int)$limit;

            $params = [$vectorStr];
        }

        return $instance->query($sql, $params);
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

        // RRF 融合
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

        $existing = $instance->queryOne("SELECT id FROM task_vectors WHERE task_id = ?", [$taskId]);

        if ($existing) {
            $sql = "UPDATE task_vectors SET 
                    project_id = ?,
                    userid = ?,
                    visibility = ?,
                    task_name = ?,
                    task_desc = ?,
                    task_content = ?,
                    content_vector = ?,
                    updated_at = NOW()
                WHERE task_id = ?";

            $params = [
                $data['project_id'] ?? 0,
                $data['userid'] ?? 0,
                $data['visibility'] ?? 1,
                $data['task_name'] ?? '',
                $data['task_desc'] ?? '',
                $data['task_content'] ?? '',
                $data['content_vector'] ?? null,
                $taskId
            ];
        } else {
            $sql = "INSERT INTO task_vectors 
                    (task_id, project_id, userid, visibility, task_name, task_desc, task_content, content_vector, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                $taskId,
                $data['project_id'] ?? 0,
                $data['userid'] ?? 0,
                $data['visibility'] ?? 1,
                $data['task_name'] ?? '',
                $data['task_desc'] ?? '',
                $data['task_content'] ?? '',
                $data['content_vector'] ?? null
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
            "UPDATE task_vectors SET visibility = ?, updated_at = NOW() WHERE task_id = ?",
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

        $existing = $instance->queryOne(
            "SELECT id FROM task_users WHERE task_id = ? AND userid = ?",
            [$taskId, $userid]
        );

        if ($existing) {
            return true;
        }

        return $instance->execute(
            "INSERT INTO task_users (task_id, userid) VALUES (?, ?)",
            [$taskId, $userid]
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
                $instance->execute(
                    "INSERT INTO task_users (task_id, userid) VALUES (?, ?)",
                    [$taskId, (int)$userid]
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SeekDB syncTaskUsers error: ' . $e->getMessage());
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

