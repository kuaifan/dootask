<?php

namespace App\Module\Manticore;

use App\Models\ManticoreSyncFailure;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
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
        $this->host = env('SEARCH_HOST', 'search');
        $this->port = (int) env('SEARCH_PORT', 9306);
    }

    /**
     * 获取 PDO 连接
     */
    private function getConnection(): ?PDO
    {
        if (!Apps::isInstalled("search")) {
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
            // charset_table='non_cjk, cjk' 同时支持英文和中日韩文字
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
                    allowed_users MULTI,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='non_cjk, cjk' morphology='icu_chinese'
            ");

            // 创建键值存储表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS key_values (
                    id BIGINT,
                    k STRING,
                    v TEXT
                )
            ");

            // 创建用户向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_vectors (
                    id BIGINT,
                    userid BIGINT,
                    nickname TEXT,
                    email STRING,
                    profession TEXT,
                    tags TEXT,
                    introduction TEXT,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='non_cjk, cjk' morphology='icu_chinese'
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
                    allowed_users MULTI,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='non_cjk, cjk' morphology='icu_chinese'
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
                    allowed_users MULTI,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='non_cjk, cjk' morphology='icu_chinese'
            ");

            // 创建消息向量表
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS msg_vectors (
                    id BIGINT,
                    msg_id BIGINT,
                    dialog_id BIGINT,
                    userid BIGINT,
                    msg_type STRING,
                    content TEXT,
                    allowed_users MULTI,
                    created_at BIGINT,
                    content_vector float_vector knn_type='hnsw' knn_dims='1536' hnsw_similarity='cosine'
                ) charset_table='non_cjk, cjk' morphology='icu_chinese'
            ");

            // Tables initialized successfully
        } catch (PDOException $e) {
            // 表可能已存在，忽略初始化错误
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
     * 判断是否为连接断开错误
     * 参考 Laravel Illuminate\Database\DetectsLostConnections
     */
    private function isConnectionLostError(PDOException $e): bool
    {
        $message = $e->getMessage();
        return stripos($message, 'server has gone away') !== false
            || stripos($message, 'no connection to the server') !== false
            || stripos($message, 'Lost connection') !== false
            || stripos($message, 'is dead or not enabled') !== false
            || stripos($message, 'Error while sending') !== false
            || stripos($message, 'decryption failed or bad record mac') !== false
            || stripos($message, 'server closed the connection unexpectedly') !== false
            || stripos($message, 'SSL connection has been closed unexpectedly') !== false
            || stripos($message, 'Error writing data to the connection') !== false
            || stripos($message, 'Resource deadlock avoided') !== false
            || stripos($message, 'Transaction() on null') !== false
            || stripos($message, 'child connection forced to terminate') !== false
            || stripos($message, 'query_wait_timeout') !== false
            || stripos($message, 'reset by peer') !== false
            || stripos($message, 'Physical connection is not usable') !== false
            || stripos($message, 'Packets out of order') !== false
            || stripos($message, 'Adaptive Server connection failed') !== false
            || stripos($message, 'Connection was killed') !== false
            || stripos($message, 'Broken pipe') !== false;
    }

    /**
     * 带重试的执行包装器
     * 正常情况零开销，仅在连接断开时重试一次
     *
     * @param callable $callback 执行回调，接收 PDO 参数
     * @param mixed $failureReturn 失败时的返回值
     * @param array $logContext 日志上下文
     * @return mixed
     */
    private function runWithRetry(callable $callback, $failureReturn = false, array $logContext = [])
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return $failureReturn;
        }

        try {
            return $callback($pdo);
        } catch (PDOException $e) {
            // 如果是连接断开错误，重置连接并重试一次
            if ($this->isConnectionLostError($e)) {
                self::resetConnection();
                $pdo = $this->getConnection();
                if ($pdo) {
                    try {
                        return $callback($pdo);
                    } catch (PDOException $retryException) {
                        Log::error('Manticore retry failed: ' . $retryException->getMessage(), $logContext);
                        return $failureReturn;
                    }
                }
            }
            Log::error('Manticore error: ' . $e->getMessage(), $logContext);
            return $failureReturn;
        }
    }

    /**
     * 检查是否已安装
     */
    public static function isInstalled(): bool
    {
        return Apps::isInstalled("search");
    }

    /**
     * 直接执行 SQL（不使用参数绑定）
     * 用于包含 MVA 或向量字段的 INSERT 语句，因为 Manticore 的 prepared statement 不支持括号表达式
     *
     * @param string $sql 完整的 SQL 语句（所有值已内联）
     * @return bool 是否成功
     */
    public function executeRaw(string $sql): bool
    {
        return $this->runWithRetry(
            function (PDO $pdo) use ($sql) {
                $pdo->exec($sql);
                return true;
            },
            false,
            ['sql' => $sql]
        );
    }

    /**
     * 转义 SQL 字符串值（用于不使用参数绑定的场景）
     *
     * @param mixed $value 要转义的值
     * @return string 转义后的值（包含引号）
     */
    public function quoteValue($value): string
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            // Fallback: 手动转义
            if (is_null($value)) {
                return 'NULL';
            }
            if (is_int($value) || is_float($value)) {
                return (string)$value;
            }
            return "'" . addslashes((string)$value) . "'";
        }

        if (is_null($value)) {
            return 'NULL';
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_float($value)) {
            return (string)$value;
        }
        return $pdo->quote((string)$value);
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
        return $this->runWithRetry(
            function (PDO $pdo) use ($sql, $params) {
                $stmt = $pdo->prepare($sql);
                $this->bindParams($stmt, $params);
                return $stmt->execute();
            },
            false,
            ['sql' => $sql, 'params' => $params]
        );
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
        return $this->runWithRetry(
            function (PDO $pdo) use ($sql, $params) {
                $stmt = $pdo->prepare($sql);
                $this->bindParams($stmt, $params);
                $stmt->execute();
                return $stmt->rowCount();
            },
            -1,
            ['sql' => $sql, 'params' => $params]
        );
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
        return $this->runWithRetry(
            function (PDO $pdo) use ($sql, $params) {
                $stmt = $pdo->prepare($sql);
                $this->bindParams($stmt, $params);
                $stmt->execute();
                return $this->convertNumericTypes($stmt->fetchAll());
            },
            [],
            ['sql' => $sql, 'params' => $params]
        );
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
        return $this->runWithRetry(
            function (PDO $pdo) use ($sql, $params) {
                $stmt = $pdo->prepare($sql);
                $this->bindParams($stmt, $params);
                $stmt->execute();
                $result = $stmt->fetch();
                return $result ? $this->convertNumericTypesRow($result) : null;
            },
            null,
            ['sql' => $sql, 'params' => $params]
        );
    }

    /**
     * 转换结果集中的数值类型
     * PDO 默认将 BIGINT 等数值类型返回为字符串，这里统一转换
     *
     * @param array $rows 结果集
     * @return array 转换后的结果集
     */
    private function convertNumericTypes(array $rows): array
    {
        return array_map([$this, 'convertNumericTypesRow'], $rows);
    }

    /**
     * 转换单行数据中的数值类型
     *
     * @param array $row 单行数据
     * @return array 转换后的数据
     */
    private function convertNumericTypesRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && is_numeric($value) && !str_contains($value, '.')) {
                $row[$key] = (int) $value;
            }
        }
        return $row;
    }

    /**
     * 绑定参数到预处理语句
     * Manticore 对参数类型敏感，需要明确指定 INT 类型
     * 注意：只有原生 int 类型才绑定为 PARAM_INT，字符串形式的数字保持为字符串
     *
     * @param \PDOStatement $stmt 预处理语句
     * @param array $params 参数数组
     */
    private function bindParams(\PDOStatement $stmt, array $params): void
    {
        $index = 1;
        foreach ($params as $value) {
            if (is_int($value)) {
                // 只有原生整数类型才绑定为 INT
                $stmt->bindValue($index, $value, PDO::PARAM_INT);
            } elseif (is_float($value)) {
                // 浮点数作为字符串传递
                $stmt->bindValue($index, (string)$value, PDO::PARAM_STR);
            } elseif (is_null($value)) {
                $stmt->bindValue($index, null, PDO::PARAM_NULL);
            } else {
                // 字符串（包括数字字符串）保持为字符串
                $stmt->bindValue($index, (string)$value, PDO::PARAM_STR);
            }
            $index++;
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
        // Manticore 特殊字符转义（完整列表）
        // 参考: https://manual.manticoresearch.com/Searching/Full_text_matching/Escaping
        $special = [
            '\\',  // 反斜杠（必须最先处理）
            '(', ')', '[', ']',  // 括号
            '|', '-', '!', '@', '~', '^', '$', '*', '?',  // 操作符
            '"', '\'',  // 引号
            '&', '/', '=', '<', '>', ':',  // 其他特殊字符
        ];
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
            // 使用 MVA 权限过滤：allowed_users = 0（公开）或 allowed_users = userid
            $sql = "
                SELECT 
                    id,
                    file_id,
                    userid,
                    pshare,
                    file_name,
                    file_type,
                    file_ext,
                    content,
                    WEIGHT() as relevance
                FROM file_vectors
                WHERE MATCH('@(file_name,content) {$escapedKeyword}')
                    AND (allowed_users = 0 OR allowed_users = " . (int)$userid . ")
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        } else {
            // 不限制权限
            $sql = "
                SELECT 
                    id,
                    file_id,
                    userid,
                    pshare,
                    file_name,
                    file_type,
                    file_ext,
                    content,
                    WEIGHT() as relevance
                FROM file_vectors
                WHERE MATCH('@(file_name,content) {$escapedKeyword}')
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        return $instance->query($sql);
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

        // KNN 搜索需要先获取更多结果，再在应用层过滤权限
        // 因为 KNN 的 WHERE 条件在 Manticore 中有限制
        $fetchLimit = $userid > 0 ? $limit * 5 : $limit;

        $sql = "
            SELECT 
                id,
                file_id,
                userid,
                pshare,
                file_name,
                file_type,
                file_ext,
                content,
                KNN_DIST() as distance
            FROM file_vectors
            WHERE KNN(content_vector, " . (int)$fetchLimit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        // 转换 distance 为 similarity（1 - distance 用于余弦距离）
        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // MVA 权限过滤
        if ($userid > 0 && !empty($results)) {
            // 获取有权限的文件列表（allowed_users 包含 0 或 userid）
            $allowedFileIds = $instance->query(
                "SELECT file_id FROM file_vectors WHERE allowed_users = 0 OR allowed_users = ? LIMIT 100000",
                [$userid]
            );
            $allowedIds = array_column($allowedFileIds, 'file_id');

            $results = array_filter($results, function ($item) use ($allowedIds) {
                return in_array($item['file_id'], $allowedIds);
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
        // 分别执行两种搜索（已包含权限过滤）
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
     * @param array $data 文件数据，包含：
     *   - file_id: 文件ID
     *   - userid: 所有者ID
     *   - pshare: 共享文件夹ID
     *   - file_name: 文件名
     *   - file_type: 文件类型
     *   - file_ext: 文件扩展名
     *   - content: 文件内容
     *   - content_vector: 向量值
     *   - allowed_users: 有权限的用户ID数组（0表示公开）
     * @return bool 是否成功
     */
    public static function upsertFileVector(array $data): bool
    {
        // 确保 id 字段与 file_id 一致
        $data['id'] = $data['file_id'] ?? 0;
        return self::upsertVector('file', $data);
    }

    /**
     * 更新文件的 allowed_users 权限列表
     *
     * @param int $fileId 文件ID
     * @param array $userids 有权限的用户ID数组
     * @return bool 是否成功
     */
    public static function updateFileAllowedUsers(int $fileId, array $userids): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $instance = new self();
        $allowedUsersStr = !empty($userids) ? '(' . implode(',', array_map('intval', $userids)) . ')' : '()';

        return $instance->execute(
            "UPDATE file_vectors SET allowed_users = {$allowedUsersStr} WHERE file_id = ?",
            [$fileId]
        );
    }

    /**
     * 删除文件向量
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function deleteFileVector(int $fileId): bool
    {
        return self::deleteVector('file', $fileId);
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
    // 用户向量方法
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
                profession,
                tags,
                introduction,
                WEIGHT() as relevance
            FROM user_vectors
            WHERE MATCH('@(nickname,profession,tags,introduction) {$escapedKeyword}')
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
                profession,
                tags,
                introduction,
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
        // 确保 id 字段与 userid 一致
        $data['id'] = $data['userid'] ?? 0;
        return self::upsertVector('user', $data);
    }

    /**
     * 删除用户向量
     *
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function deleteUserVector(int $userid): bool
    {
        return self::deleteVector('user', $userid);
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

        if ($userid > 0) {
            // 使用 MVA 权限过滤
            $sql = "
                SELECT 
                    id,
                    project_id,
                    userid,
                    personal,
                    project_name,
                    project_desc,
                    WEIGHT() as relevance
                FROM project_vectors
                WHERE MATCH('@(project_name,project_desc) {$escapedKeyword}')
                    AND allowed_users = " . (int)$userid . "
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        } else {
            $sql = "
                SELECT 
                    id,
                    project_id,
                    userid,
                    personal,
                    project_name,
                    project_desc,
                    WEIGHT() as relevance
                FROM project_vectors
                WHERE MATCH('@(project_name,project_desc) {$escapedKeyword}')
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        return $instance->query($sql);
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

        // KNN 搜索需要先获取更多结果，再在应用层过滤权限
        $fetchLimit = $userid > 0 ? $limit * 5 : $limit;

        $sql = "
            SELECT 
                id,
                project_id,
                userid,
                personal,
                project_name,
                project_desc,
                KNN_DIST() as distance
            FROM project_vectors
            WHERE KNN(content_vector, " . (int)$fetchLimit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // MVA 权限过滤
        if ($userid > 0 && !empty($results)) {
            $allowedProjectIds = $instance->query(
                "SELECT project_id FROM project_vectors WHERE allowed_users = ? LIMIT 100000",
                [$userid]
            );
            $allowedIds = array_column($allowedProjectIds, 'project_id');

            $results = array_filter($results, function ($item) use ($allowedIds) {
                return in_array($item['project_id'], $allowedIds);
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
     * @param array $data 项目数据，包含：
     *   - project_id: 项目ID
     *   - userid: 创建者ID
     *   - personal: 是否个人项目
     *   - project_name: 项目名称
     *   - project_desc: 项目描述
     *   - content_vector: 向量值
     *   - allowed_users: 有权限的用户ID数组
     * @return bool 是否成功
     */
    public static function upsertProjectVector(array $data): bool
    {
        // 确保 id 字段与 project_id 一致
        $data['id'] = $data['project_id'] ?? 0;
        return self::upsertVector('project', $data);
    }

    /**
     * 更新项目的 allowed_users 权限列表
     *
     * @param int $projectId 项目ID
     * @param array $userids 有权限的用户ID数组
     * @return bool 是否成功
     */
    public static function updateProjectAllowedUsers(int $projectId, array $userids): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $instance = new self();
        $allowedUsersStr = !empty($userids) ? '(' . implode(',', array_map('intval', $userids)) . ')' : '()';

        return $instance->execute(
            "UPDATE project_vectors SET allowed_users = {$allowedUsersStr} WHERE project_id = ?",
            [$projectId]
        );
    }

    /**
     * 删除项目向量
     *
     * @param int $projectId 项目ID
     * @return bool 是否成功
     */
    public static function deleteProjectVector(int $projectId): bool
    {
        return self::deleteVector('project', $projectId);
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

        if ($userid > 0) {
            // 使用 MVA 权限过滤
            $sql = "
                SELECT 
                    id,
                    task_id,
                    project_id,
                    userid,
                    visibility,
                    task_name,
                    task_desc,
                    task_content,
                    WEIGHT() as relevance
                FROM task_vectors
                WHERE MATCH('@(task_name,task_desc,task_content) {$escapedKeyword}')
                    AND allowed_users = " . (int)$userid . "
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        } else {
            $sql = "
                SELECT 
                    id,
                    task_id,
                    project_id,
                    userid,
                    visibility,
                    task_name,
                    task_desc,
                    task_content,
                    WEIGHT() as relevance
                FROM task_vectors
                WHERE MATCH('@(task_name,task_desc,task_content) {$escapedKeyword}')
                ORDER BY relevance DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        return $instance->query($sql);
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

        // KNN 搜索需要先获取更多结果，再在应用层过滤权限
        $fetchLimit = $userid > 0 ? $limit * 5 : $limit;

        $sql = "
            SELECT 
                id,
                task_id,
                project_id,
                userid,
                visibility,
                task_name,
                task_desc,
                task_content,
                KNN_DIST() as distance
            FROM task_vectors
            WHERE KNN(content_vector, " . (int)$fetchLimit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // MVA 权限过滤
        if ($userid > 0 && !empty($results)) {
            $allowedTaskIds = $instance->query(
                "SELECT task_id FROM task_vectors WHERE allowed_users = ? LIMIT 100000",
                [$userid]
            );
            $allowedIds = array_column($allowedTaskIds, 'task_id');

            $results = array_filter($results, function ($item) use ($allowedIds) {
                return in_array($item['task_id'], $allowedIds);
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
     * @param array $data 任务数据，包含：
     *   - task_id: 任务ID
     *   - project_id: 项目ID
     *   - userid: 创建者ID
     *   - visibility: 可见性
     *   - task_name: 任务名称
     *   - task_desc: 任务描述
     *   - task_content: 任务内容
     *   - content_vector: 向量值
     *   - allowed_users: 有权限的用户ID数组
     * @return bool 是否成功
     */
    public static function upsertTaskVector(array $data): bool
    {
        // 确保 id 字段与 task_id 一致
        $data['id'] = $data['task_id'] ?? 0;
        return self::upsertVector('task', $data);
    }

    /**
     * 更新任务的 allowed_users 权限列表
     *
     * @param int $taskId 任务ID
     * @param array $userids 有权限的用户ID数组
     * @return bool 是否成功
     */
    public static function updateTaskAllowedUsers(int $taskId, array $userids): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        $instance = new self();
        $allowedUsersStr = !empty($userids) ? '(' . implode(',', array_map('intval', $userids)) . ')' : '()';

        return $instance->execute(
            "UPDATE task_vectors SET allowed_users = {$allowedUsersStr} WHERE task_id = ?",
            [$taskId]
        );
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
        return self::deleteVector('task', $taskId);
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
    // 消息向量方法
    // ==============================

    /**
     * 消息全文搜索
     *
     * @param string $keyword 关键词
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @param int $dialogId 对话ID（0表示不限制）
     * @return array 搜索结果
     */
    public static function msgFullTextSearch(string $keyword, int $userid = 0, int $limit = 20, int $offset = 0, int $dialogId = 0): array
    {
        if (empty($keyword)) {
            return [];
        }

        $instance = new self();
        $escapedKeyword = self::escapeMatch($keyword);

        // 构建过滤条件
        $conditions = ["MATCH('@content {$escapedKeyword}')"];
        if ($userid > 0) {
            $conditions[] = "allowed_users = " . (int)$userid;
        }
        if ($dialogId > 0) {
            $conditions[] = "dialog_id = " . (int)$dialogId;
        }
        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT 
                id,
                msg_id,
                dialog_id,
                userid,
                msg_type,
                content,
                created_at,
                WEIGHT() as relevance
            FROM msg_vectors
            WHERE {$whereClause}
            ORDER BY relevance DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $instance->query($sql);
    }

    /**
     * 消息向量搜索
     *
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @param int $dialogId 对话ID（0表示不限制）
     * @return array 搜索结果
     */
    public static function msgVectorSearch(array $queryVector, int $userid = 0, int $limit = 20, int $dialogId = 0): array
    {
        if (empty($queryVector)) {
            return [];
        }

        $instance = new self();
        $vectorStr = '(' . implode(',', $queryVector) . ')';

        // KNN 搜索需要先获取更多结果，再在应用层过滤权限和对话
        $needFilter = $userid > 0 || $dialogId > 0;
        $fetchLimit = $needFilter ? $limit * 5 : $limit;

        $sql = "
            SELECT 
                id,
                msg_id,
                dialog_id,
                userid,
                msg_type,
                content,
                created_at,
                KNN_DIST() as distance
            FROM msg_vectors
            WHERE KNN(content_vector, " . (int)$fetchLimit . ", {$vectorStr})
            ORDER BY distance ASC
        ";

        $results = $instance->query($sql);

        foreach ($results as &$item) {
            $item['similarity'] = 1 - ($item['distance'] ?? 0);
        }

        // MVA 权限过滤
        if ($userid > 0 && !empty($results)) {
            $allowedMsgIds = $instance->query(
                "SELECT msg_id FROM msg_vectors WHERE allowed_users = ? LIMIT 100000",
                [$userid]
            );
            $allowedIds = array_column($allowedMsgIds, 'msg_id');

            $results = array_filter($results, function ($item) use ($allowedIds) {
                return in_array($item['msg_id'], $allowedIds);
            });
            $results = array_values($results);
        }

        // 对话过滤
        if ($dialogId > 0 && !empty($results)) {
            $results = array_filter($results, function ($item) use ($dialogId) {
                return $item['dialog_id'] == $dialogId;
            });
            $results = array_values($results);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * 消息混合搜索
     *
     * @param string $keyword 关键词
     * @param array $queryVector 查询向量
     * @param int $userid 用户ID（权限过滤）
     * @param int $limit 返回数量
     * @param int $dialogId 对话ID（0表示不限制）
     * @return array 搜索结果
     */
    public static function msgHybridSearch(string $keyword, array $queryVector, int $userid = 0, int $limit = 20, int $dialogId = 0): array
    {
        $textResults = self::msgFullTextSearch($keyword, $userid, 50, 0, $dialogId);
        $vectorResults = !empty($queryVector) ? self::msgVectorSearch($queryVector, $userid, 50, $dialogId) : [];

        $scores = [];
        $items = [];
        $k = 60;

        foreach ($textResults as $rank => $item) {
            $id = $item['msg_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 / ($k + $rank + 1);
            $items[$id] = $item;
        }

        foreach ($vectorResults as $rank => $item) {
            $id = $item['msg_id'];
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
     * 插入或更新消息向量
     *
     * @param array $data 消息数据，包含：
     *   - msg_id: 消息ID
     *   - dialog_id: 对话ID
     *   - userid: 发送者ID
     *   - msg_type: 消息类型
     *   - content: 消息内容
     *   - content_vector: 向量值
     *   - allowed_users: 有权限的用户ID数组
     *   - created_at: 创建时间戳
     * @return bool 是否成功
     */
    public static function upsertMsgVector(array $data): bool
    {
        // 确保 id 字段与 msg_id 一致
        $data['id'] = $data['msg_id'] ?? 0;
        return self::upsertVector('msg', $data);
    }

    /**
     * 更新对话的 allowed_users 权限列表（批量更新该对话下所有消息）
     *
     * @param int $dialogId 对话ID
     * @param array $userids 有权限的用户ID数组
     * @return int 更新的消息数量
     */
    public static function updateDialogAllowedUsers(int $dialogId, array $userids): int
    {
        if ($dialogId <= 0) {
            return 0;
        }

        $instance = new self();
        $allowedUsersStr = !empty($userids) ? '(' . implode(',', array_map('intval', $userids)) . ')' : '()';

        // Manticore 支持按条件批量更新
        return $instance->executeWithRowCount(
            "UPDATE msg_vectors SET allowed_users = {$allowedUsersStr} WHERE dialog_id = ?",
            [$dialogId]
        );
    }

    /**
     * 删除消息向量
     *
     * @param int $msgId 消息ID
     * @return bool 是否成功
     */
    public static function deleteMsgVector(int $msgId): bool
    {
        return self::deleteVector('msg', $msgId);
    }

    /**
     * 批量删除对话下的所有消息向量
     *
     * @param int $dialogId 对话ID
     * @return int 删除数量
     */
    public static function deleteDialogMsgVectors(int $dialogId): int
    {
        if ($dialogId <= 0) {
            return 0;
        }

        $instance = new self();
        return $instance->executeWithRowCount(
            "DELETE FROM msg_vectors WHERE dialog_id = ?",
            [$dialogId]
        );
    }

    /**
     * 清空所有消息向量
     *
     * @return bool 是否成功
     */
    public static function clearAllMsgVectors(): bool
    {
        $instance = new self();
        return $instance->execute("TRUNCATE TABLE msg_vectors");
    }

    /**
     * 获取已索引的消息数量
     *
     * @return int 消息数量
     */
    public static function getIndexedMsgCount(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT COUNT(*) as cnt FROM msg_vectors");
        return $result ? (int) $result['cnt'] : 0;
    }

    /**
     * 获取对话的已索引消息数量
     *
     * @param int $dialogId 对话ID
     * @return int 消息数量
     */
    public static function getDialogIndexedMsgCount(int $dialogId): int
    {
        if ($dialogId <= 0) {
            return 0;
        }

        $instance = new self();
        $result = $instance->queryOne(
            "SELECT COUNT(*) as cnt FROM msg_vectors WHERE dialog_id = ?",
            [$dialogId]
        );
        return $result ? (int) $result['cnt'] : 0;
    }

    /**
     * 获取最后索引的消息ID
     *
     * @return int 消息ID
     */
    public static function getLastIndexedMsgId(): int
    {
        $instance = new self();
        $result = $instance->queryOne("SELECT MAX(msg_id) as max_id FROM msg_vectors");
        return $result ? (int) ($result['max_id'] ?? 0) : 0;
    }

    // ==============================
    // 向量更新方法
    // ==============================

    /**
     * 数值类型字段列表（用于 SQL 值构建时判断是否需要引号）
     */
    private const NUMERIC_FIELDS = [
        'id', 'userid', 'pshare', 'visibility', 'personal',
        'msg_id', 'file_id', 'task_id', 'project_id', 'dialog_id', 'created_at'
    ];

    /**
     * 向量表配置
     * 定义各类型的表名、主键字段、普通字段、MVA字段
     */
    private const VECTOR_TABLE_CONFIG = [
        'msg' => [
            'table' => 'msg_vectors',
            'pk' => 'msg_id',
            'fields' => ['id', 'msg_id', 'dialog_id', 'userid', 'msg_type', 'content', 'created_at'],
            'mva_fields' => ['allowed_users'],
        ],
        'file' => [
            'table' => 'file_vectors',
            'pk' => 'file_id',
            'fields' => ['id', 'file_id', 'userid', 'pshare', 'file_name', 'file_type', 'file_ext', 'content'],
            'mva_fields' => ['allowed_users'],
        ],
        'task' => [
            'table' => 'task_vectors',
            'pk' => 'task_id',
            'fields' => ['id', 'task_id', 'project_id', 'userid', 'visibility', 'task_name', 'task_desc', 'task_content'],
            'mva_fields' => ['allowed_users'],
        ],
        'project' => [
            'table' => 'project_vectors',
            'pk' => 'project_id',
            'fields' => ['id', 'project_id', 'userid', 'personal', 'project_name', 'project_desc'],
            'mva_fields' => ['allowed_users'],
        ],
        'user' => [
            'table' => 'user_vectors',
            'pk' => 'userid',
            'fields' => ['id', 'userid', 'nickname', 'email', 'profession', 'tags', 'introduction'],
            'mva_fields' => [],
        ],
    ];

    /**
     * 通用向量插入方法
     *
     * 使用 executeRaw 直接执行 SQL，避免 Manticore prepared statement
     * 无法解析 MVA 和向量字段括号语法的问题。
     *
     * @param string $type 类型: msg/file/task/project/user
     * @param array $data 数据，键名对应字段名
     * @return bool 是否成功
     */
    public static function upsertVector(string $type, array $data): bool
    {
        if (!isset(self::VECTOR_TABLE_CONFIG[$type])) {
            return false;
        }

        $config = self::VECTOR_TABLE_CONFIG[$type];
        $table = $config['table'];
        $pk = $config['pk'];
        $fields = $config['fields'];
        $mvaFields = $config['mva_fields'];

        // 检查主键
        $pkValue = $data[$pk] ?? 0;
        if ($pkValue <= 0) {
            return false;
        }

        $instance = new self();

        // 先删除已存在的记录
        $instance->execute("DELETE FROM {$table} WHERE {$pk} = ?", [$pkValue]);

        // 构建字段列表和值
        $fieldList = [];
        $valueList = [];

        // 处理普通字段
        foreach ($fields as $field) {
            $fieldList[] = $field;
            $value = $data[$field] ?? ($field === 'created_at' ? time() : (in_array($field, self::NUMERIC_FIELDS) ? 0 : ''));

            if (in_array($field, self::NUMERIC_FIELDS)) {
                $valueList[] = (int)$value;
            } else {
                $valueList[] = $instance->quoteValue((string)$value);
            }
        }

        // 处理 MVA 字段
        foreach ($mvaFields as $mvaField) {
            $fieldList[] = $mvaField;
            $mvaData = $data[$mvaField] ?? [];
            $valueList[] = !empty($mvaData)
                ? '(' . implode(',', array_map('intval', $mvaData)) . ')'
                : '()';
        }

        // 处理向量字段
        $vectorValue = $data['content_vector'] ?? null;
        if ($vectorValue) {
            $fieldList[] = 'content_vector';
            $valueList[] = str_replace(['[', ']'], ['(', ')'], $vectorValue);
        }

        // 构建并执行 SQL
        $sql = "INSERT INTO {$table} (" . implode(', ', $fieldList) . ") VALUES (" . implode(', ', $valueList) . ")";

        $result = $instance->executeRaw($sql);

        // 记录同步结果
        if ($result) {
            // 成功则删除失败记录（如果有）
            ManticoreSyncFailure::removeSuccess($type, $pkValue, 'sync');
        } else {
            // 失败则记录
            ManticoreSyncFailure::recordFailure($type, $pkValue, 'sync', "INSERT failed for {$table}");
        }

        return $result;
    }

    /**
     * 通用向量删除方法
     *
     * @param string $type 类型: msg/file/task/project/user
     * @param int $id 数据ID
     * @return bool 是否成功
     */
    public static function deleteVector(string $type, int $id): bool
    {
        if (!isset(self::VECTOR_TABLE_CONFIG[$type]) || $id <= 0) {
            return false;
        }

        $config = self::VECTOR_TABLE_CONFIG[$type];
        $table = $config['table'];
        $pk = $config['pk'];

        $instance = new self();
        $result = $instance->execute("DELETE FROM {$table} WHERE {$pk} = ?", [$id]);

        // 记录删除结果
        if ($result) {
            // 成功则删除失败记录（如果有）
            ManticoreSyncFailure::removeSuccess($type, $id, 'delete');
        } else {
            // 失败则记录
            ManticoreSyncFailure::recordFailure($type, $id, 'delete', "DELETE failed for {$table}");
        }

        return $result;
    }

    /**
     * 通用批量更新向量方法（高性能版本）
     *
     * 优化：将 N 条记录的 3N 次操作减少为 N+2 次操作
     * 1. 批量 SELECT 获取现有记录 (1次)
     * 2. 预构建所有 INSERT SQL（验证数据完整性）
     * 3. 批量 DELETE 删除旧记录 (1次)
     * 4. 逐条 INSERT 新记录带向量 (N次，因向量字段无法批量绑定)
     *
     * @param string $type 类型: msg/file/task/project/user
     * @param array $vectorData 向量数据 [pk_value => vectorStr, ...]
     * @return int 成功更新的数量
     */
    public static function batchUpdateVectors(string $type, array $vectorData): int
    {
        if (empty($vectorData) || !isset(self::VECTOR_TABLE_CONFIG[$type])) {
            return 0;
        }

        $config = self::VECTOR_TABLE_CONFIG[$type];
        $table = $config['table'];
        $pk = $config['pk'];
        $fields = $config['fields'];
        $mvaFields = $config['mva_fields'];

        $instance = new self();
        $ids = array_keys($vectorData);

        // 1. 批量查询现有记录
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $existingRows = $instance->query(
            "SELECT * FROM {$table} WHERE {$pk} IN ({$placeholders})",
            $ids
        );

        if (empty($existingRows)) {
            return 0;
        }

        // 建立 pk => row 的映射
        $existingMap = [];
        foreach ($existingRows as $row) {
            $existingMap[$row[$pk]] = $row;
        }

        $idsToUpdate = array_keys($existingMap);
        if (empty($idsToUpdate)) {
            return 0;
        }

        // 2. 预构建所有 INSERT 语句（在删除前验证数据完整性）
        $insertStatements = [];
        foreach ($idsToUpdate as $pkValue) {
            $existing = $existingMap[$pkValue];
            $vectorStr = $vectorData[$pkValue] ?? null;

            if (empty($vectorStr)) {
                continue;
            }

            // Manticore 向量使用 () 格式
            $vectorStr = str_replace(['[', ']'], ['(', ')'], $vectorStr);

            // 构建字段列表和值（直接内联值，不使用参数绑定）
            $fieldList = $fields;
            $quotedValues = [];
            foreach ($fields as $field) {
                $value = $existing[$field] ?? null;
                // 处理默认值：数值字段用 0，时间戳字段用当前时间，其他用空字符串
                if ($value === null) {
                    if ($field === 'created_at') {
                        $value = time();
                    } elseif (in_array($field, self::NUMERIC_FIELDS)) {
                        $value = 0;
                    } else {
                        $value = '';
                    }
                }
                // 根据字段类型处理值
                if (in_array($field, self::NUMERIC_FIELDS)) {
                    $quotedValues[] = (int)$value;
                } else {
                    $quotedValues[] = $instance->quoteValue((string)$value);
                }
            }

            // 构建 MVA 字段
            $mvaValuesStr = [];
            foreach ($mvaFields as $mvaField) {
                $fieldList[] = $mvaField;
                $mvaValuesStr[] = !empty($existing[$mvaField])
                    ? '(' . $existing[$mvaField] . ')'
                    : '()';
            }

            // 添加向量字段
            $fieldList[] = 'content_vector';

            // 构建 SQL（所有值直接内联，使用 executeRaw 避免 prepared statement 解析问题）
            $allValues = implode(', ', array_merge($quotedValues, $mvaValuesStr, [$vectorStr]));
            $sql = "INSERT INTO {$table} (" . implode(', ', $fieldList) . ") VALUES ({$allValues})";

            $insertStatements[] = ['sql' => $sql, 'pk' => $pkValue];
        }

        // 如果没有有效的插入语句，直接返回
        if (empty($insertStatements)) {
            return 0;
        }

        // 3. 批量删除旧记录（只删除有有效向量的记录）
        $validPks = array_column($insertStatements, 'pk');
        $deletePlaceholders = implode(',', array_fill(0, count($validPks), '?'));
        $instance->execute(
            "DELETE FROM {$table} WHERE {$pk} IN ({$deletePlaceholders})",
            $validPks
        );

        // 4. 逐条插入新记录（使用 executeRaw 避免 prepared statement 解析问题）
        $successCount = 0;
        foreach ($insertStatements as $stmt) {
            if ($instance->executeRaw($stmt['sql'])) {
                $successCount++;
                // 成功则删除失败记录（如果有）
                ManticoreSyncFailure::removeSuccess($type, $stmt['pk'], 'sync');
            } else {
                // 失败则记录
                ManticoreSyncFailure::recordFailure($type, $stmt['pk'], 'sync', "Batch INSERT failed for {$table}");
            }
        }

        return $successCount;
    }

    /**
     * 批量更新消息向量（兼容方法）
     */
    public static function batchUpdateMsgVectors(array $vectorData): int
    {
        return self::batchUpdateVectors('msg', $vectorData);
    }

    /**
     * 批量更新文件向量
     */
    public static function batchUpdateFileVectors(array $vectorData): int
    {
        return self::batchUpdateVectors('file', $vectorData);
    }

    /**
     * 批量更新任务向量
     */
    public static function batchUpdateTaskVectors(array $vectorData): int
    {
        return self::batchUpdateVectors('task', $vectorData);
    }

    /**
     * 批量更新项目向量
     */
    public static function batchUpdateProjectVectors(array $vectorData): int
    {
        return self::batchUpdateVectors('project', $vectorData);
    }

    /**
     * 批量更新用户向量
     */
    public static function batchUpdateUserVectors(array $vectorData): int
    {
        return self::batchUpdateVectors('user', $vectorData);
    }

    // ==============================
    // 通用工具方法
    // ==============================

    /**
     * 获取文本的 Embedding 向量
     *
     * @param string $text 文本
     * @return array 向量数组（空数组表示失败）
     */
    public static function getEmbedding(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        try {
            $result = AI::getEmbedding($text);
            if (Base::isSuccess($result)) {
                return $result['data'] ?? [];
            }
        } catch (\Exception $e) {
            // embedding 获取失败，返回空数组
        }

        return [];
    }

}

