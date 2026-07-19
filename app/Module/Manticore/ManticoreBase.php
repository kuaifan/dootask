<?php

namespace App\Module\Manticore;

use App\Models\ManticoreSyncFailure;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use PDO;
use PDOException;
use Illuminate\Support\Facades\Cache;
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

    /**
     * 向量表结构版本；修改表结构/向量列参数时递增，触发已部署实例自动重建
     */
    private const SCHEMA_VERSION = 1;

    /**
     * Auto Embeddings 的 MODEL_NAME。必须用 Manticore 不认识的名字：
     * 已知名字（如 text-embedding-ada-002）会按引擎硬编码维度校验，
     * 未知名字才会在建表时向 API_URL 探测真实维度（免费模型为 1024）。
     * 实际模型由 ai 插件的 EMBEDDING_MODEL 决定，此处仅为路由标签。
     */
    private const EMBEDDING_MODEL_NAME = 'openai/qwen3-embedding';

    /**
     * 批量写入分块上限：行数与字节预算（Manticore max_allowed_packet 默认 128MB，取保守值）
     */
    private const BATCH_CHUNK_ROWS = 30;
    private const BATCH_CHUNK_BYTES = 8388608;

    /**
     * 5 张向量表名（键值即 VECTOR_TABLE_CONFIG 的 type）
     */
    private const VECTOR_TABLES = ['msg', 'file', 'task', 'project', 'user'];

    private string $host;
    private int $port;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->host = config('dootask.search_host');
        $this->port = (int) config('dootask.search_port');
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
     *
     * 向量列使用 Manticore Auto Embeddings（MODEL_NAME/API_URL 指向 ai 插件 /embeddings），
     * 引擎在写入/更新行时自动按 FROM 字段生成向量，无需 PHP 侧生成。
     * key_values 中的 vector:schema 标记记录当前结构指纹（结构版本/模型/端点/APP_KEY 哈希），
     * 不匹配（首次安装、老版本升级、APP_KEY 轮换）即整体重建并重置同步指针，触发全量重灌。
     */
    private function initializeTables(PDO $pdo): void
    {
        try {
            // 键值表必须最先建（用于读取/持久化结构标记与同步指针）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS key_values (
                    id BIGINT,
                    k STRING,
                    v TEXT
                )
            ");

            $expected = self::schemaMarker();
            if (self::kvGetPdo($pdo, 'vector:schema') === $expected
                && self::allVectorTablesExist($pdo)
                && !self::embeddingModelChanged($pdo)) {
                return;
            }

            // 并发进程只允许一个执行重建；抢锁失败的进程本轮写入失败会进重试队列，无碍
            $lock = Cache::lock('manticore:schema-rebuild', 300);
            if (!$lock->get()) {
                return;
            }
            try {
                // 拿到锁时可能另一进程刚完成重建（marker 写入在锁内），先复检避免重复重建
                if (self::kvGetPdo($pdo, 'vector:schema') === $expected
                    && self::allVectorTablesExist($pdo)
                    && !self::embeddingModelChanged($pdo)) {
                    return;
                }

                // 先用一次性探针表验证 ai 端点可用（CREATE 会向端点探测维度、未就绪则抛错），
                // 通过后才 DROP 现有表——避免 ai 未就绪时销毁旧索引后建不回来
                $pdo->exec("DROP TABLE IF EXISTS _schema_probe");
                $pdo->exec("CREATE TABLE _schema_probe (t TEXT, " . self::vectorColumnDDL('t') . ")");
                $pdo->exec("DROP TABLE IF EXISTS _schema_probe");

                foreach (self::vectorTableDDLs() as $table => $ddl) {
                    $pdo->exec("DROP TABLE IF EXISTS {$table}");
                    $pdo->exec($ddl);
                }

                self::resetSyncPointersPdo($pdo);
                // 清理旧向量管道遗留键（vector:dim 与 vector:*LastId 指针）
                $legacy = ["'vector:dim'"];
                foreach (self::VECTOR_TABLES as $t) {
                    $legacy[] = "'vector:manticore" . ucfirst($t) . "LastId'";
                }
                $pdo->exec("DELETE FROM key_values WHERE k IN (" . implode(',', $legacy) . ")");
                self::rememberEmbeddingModel($pdo);
                // marker 最后写入且在锁内：写入即代表重建完整成功
                self::kvSetPdo($pdo, 'vector:schema', $expected);
                Log::info("Manticore vector tables rebuilt for auto-embeddings schema {$expected}");
            } catch (\Throwable $e) {
                // 重建失败（如 ai 插件未就绪/未升级）：不写 marker，下个进程重试，可自愈
                Log::error('Manticore schema rebuild failed: ' . $e->getMessage());
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            Log::error('Manticore initializeTables failed: ' . $e->getMessage());
        }
    }

    /**
     * ai 插件向量化端点地址（唯一来源在 AI::embeddingsUrl，查询侧与表定义共用）
     */
    private static function embeddingsApiUrl(): string
    {
        return AI::embeddingsUrl();
    }

    /**
     * 写入表定义的派生密钥：sha256(APP_KEY:embeddings)，与 ai 插件约定一致。
     * 不直接写 APP_KEY（Laravel 主密钥），避免其落入搜索引擎元数据/数据卷；
     * 派生值不可反推，且仅授予 /embeddings 调用权限。
     */
    private static function embeddingsApiKey(): string
    {
        return hash('sha256', config('app.key') . ':embeddings');
    }

    /**
     * 当前向量表结构指纹：结构版本/模型名/端点/APP_KEY 哈希任一变化都会触发整体重建
     */
    private static function schemaMarker(): string
    {
        return md5(self::SCHEMA_VERSION . '|' . self::EMBEDDING_MODEL_NAME . '|'
            . self::embeddingsApiUrl() . '|' . hash('sha256', (string) config('app.key')));
    }

    /**
     * 检测 ai 插件实际生效的向量模型是否与建表时不一致（不一致需重建，否则维度可能不匹配）。
     *
     * 实际模型（EMBEDDING_MODEL env）对主程序不可见，由查询侧在成功请求后
     * 写入缓存 ai:embedding_model；建表时的模型记录在 key_values 的 vector:model。
     * 任一侧未知时不触发（返回 false），已知且存量缺失时顺手补记。
     */
    private static function embeddingModelChanged(PDO $pdo): bool
    {
        $live = (string) Cache::get('ai:embedding_model', '');
        if ($live === '') {
            return false;
        }
        $stored = self::kvGetPdo($pdo, 'vector:model');
        if ($stored === null || $stored === '') {
            // 旧部署/首次：补记当前模型，不触发重建
            self::kvSetPdo($pdo, 'vector:model', $live);
            return false;
        }
        return $stored !== $live;
    }

    /**
     * 重建成功后记录当前生效的向量模型（优先取查询侧维护的缓存值）
     */
    private static function rememberEmbeddingModel(PDO $pdo): void
    {
        $live = (string) Cache::get('ai:embedding_model', '');
        if ($live !== '') {
            self::kvSetPdo($pdo, 'vector:model', $live);
        } else {
            // 未知则清掉存量，待查询侧探得后由 embeddingModelChanged 补记
            $pdo->exec("DELETE FROM key_values WHERE k = 'vector:model'");
        }
    }

    /**
     * 生成 Auto Embeddings 向量列定义（引擎按 FROM 字段自动生成/更新向量）
     *
     * 注意不写 KNN_DIMS（与 MODEL_NAME 互斥），维度由引擎建表时向端点探测。
     *
     * @param string $from 参与向量化的字段（镜像旧 PHP 管道的拼接字段，逗号分隔）
     */
    private static function vectorColumnDDL(string $from): string
    {
        return "content_vector float_vector knn_type='hnsw' hnsw_similarity='cosine'"
            . " MODEL_NAME='" . self::EMBEDDING_MODEL_NAME . "'"
            . " FROM='{$from}'"
            . " API_KEY='" . self::embeddingsApiKey() . "'"
            . " API_URL='" . self::embeddingsApiUrl() . "'"
            . " API_TIMEOUT='60'";
    }

    /**
     * 生成 5 张向量表的建表语句
     *
     * charset_table='non_cjk, cjk' 同时支持英文和中日韩文字
     *
     * @return array [table => DDL]
     */
    private static function vectorTableDDLs(): array
    {
        $tail = "\n                ) charset_table='non_cjk, cjk' morphology='icu_chinese'";
        return [
            'file_vectors' => "
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
                    " . self::vectorColumnDDL('file_name,content') . $tail,
            'user_vectors' => "
                CREATE TABLE IF NOT EXISTS user_vectors (
                    id BIGINT,
                    userid BIGINT,
                    nickname TEXT,
                    email STRING,
                    profession TEXT,
                    tags TEXT,
                    introduction TEXT,
                    " . self::vectorColumnDDL('nickname,email,profession,tags,introduction') . $tail,
            'project_vectors' => "
                CREATE TABLE IF NOT EXISTS project_vectors (
                    id BIGINT,
                    project_id BIGINT,
                    userid BIGINT,
                    personal INTEGER,
                    project_name TEXT,
                    project_desc TEXT,
                    allowed_users MULTI,
                    " . self::vectorColumnDDL('project_name,project_desc') . $tail,
            'task_vectors' => "
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
                    " . self::vectorColumnDDL('task_name,task_desc,task_content') . $tail,
            'msg_vectors' => "
                CREATE TABLE IF NOT EXISTS msg_vectors (
                    id BIGINT,
                    msg_id BIGINT,
                    dialog_id BIGINT,
                    userid BIGINT,
                    msg_type STRING,
                    content TEXT,
                    allowed_users MULTI,
                    created_at BIGINT,
                    " . self::vectorColumnDDL('content') . $tail,
        ];
    }

    /**
     * 检查 5 张向量表是否都已存在
     */
    private static function allVectorTablesExist(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SHOW TABLES");
            $existing = [];
            foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
                $existing[$row[0]] = true;
            }
            foreach (array_keys(self::vectorTableDDLs()) as $table) {
                if (!isset($existing[$table])) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 直接用 PDO 读取 key_values（避免初始化期通过 ManticoreKeyValue 造成递归）
     */
    private static function kvGetPdo(PDO $pdo, string $key): ?string
    {
        try {
            $stmt = $pdo->prepare("SELECT v FROM key_values WHERE k = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($row && isset($row['v'])) ? (string)$row['v'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 直接用 PDO 写入 key_values（同 ManticoreKeyValue::set 的 id 规则）
     */
    private static function kvSetPdo(PDO $pdo, string $key, string $value): void
    {
        try {
            $del = $pdo->prepare("DELETE FROM key_values WHERE k = ?");
            $del->execute([$key]);
            $ins = $pdo->prepare("INSERT INTO key_values (id, k, v) VALUES (?, ?, ?)");
            $ins->execute([abs(crc32($key)), $key, $value]);
        } catch (\Throwable $e) {
            // 忽略
        }
    }

    /**
     * 重置全文同步进度指针（sync:*），触发全量重灌（向量由引擎随行自动生成）
     */
    private static function resetSyncPointersPdo(PDO $pdo): void
    {
        foreach (self::VECTOR_TABLES as $t) {
            self::kvSetPdo($pdo, "sync:manticore" . ucfirst($t) . "LastId", '0');
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
        // 日志上下文只保留 SQL 前 2KB：多行批量语句可达数 MB，完整写入会淹没日志
        $sqlPreview = strlen($sql) > 2048
            ? substr($sql, 0, 2048) . ' ...[+' . (strlen($sql) - 2048) . ' bytes]'
            : $sql;
        return $this->runWithRetry(
            function (PDO $pdo) use ($sql) {
                $pdo->exec($sql);
                return true;
            },
            false,
            ['sql' => $sqlPreview]
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
     * 通用单行写入方法（REPLACE，向量由引擎 Auto Embeddings 自动生成）
     *
     * 使用 executeRaw 直接执行 SQL，避免 Manticore prepared statement
     * 无法解析 MVA 字段括号语法的问题。
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

        // 检查主键
        $pkValue = $data[$pk] ?? 0;
        if ($pkValue <= 0) {
            return false;
        }

        $instance = new self();
        [$fieldList, $valueList] = $instance->buildRowValues($config, $data);

        // REPLACE 按 id 原子替换整行，向量列由引擎按 FROM 字段自动重新生成。
        // 前提：所有 upsertXxxVector 均强制 id = 主键值，故 REPLACE(按 id) 与按主键去重等价
        $sql = "REPLACE INTO {$table} (" . implode(', ', $fieldList) . ") VALUES (" . implode(', ', $valueList) . ")";

        $result = $instance->executeRaw($sql);

        // 记录同步结果
        if ($result) {
            // 成功则删除失败记录（如果有）
            ManticoreSyncFailure::removeSuccess($type, $pkValue, 'sync');
        } else {
            // 失败则记录
            ManticoreSyncFailure::recordFailure($type, $pkValue, 'sync', "REPLACE failed for {$table}");
        }

        return $result;
    }

    /**
     * 构建一行数据的字段列表与内联值（普通字段 + MVA 字段，向量列由引擎自动生成，不在此列）
     *
     * @param array $config VECTOR_TABLE_CONFIG 中的类型配置
     * @param array $data 行数据
     * @return array [fieldList, valueList]
     */
    private function buildRowValues(array $config, array $data): array
    {
        $fieldList = [];
        $valueList = [];

        foreach ($config['fields'] as $field) {
            $fieldList[] = $field;
            $value = $data[$field] ?? ($field === 'created_at' ? time() : (in_array($field, self::NUMERIC_FIELDS) ? 0 : ''));

            if (in_array($field, self::NUMERIC_FIELDS)) {
                $valueList[] = (int)$value;
            } else {
                $valueList[] = $this->quoteValue((string)$value);
            }
        }

        foreach ($config['mva_fields'] as $mvaField) {
            $fieldList[] = $mvaField;
            $mvaData = $data[$mvaField] ?? [];
            $valueList[] = !empty($mvaData)
                ? '(' . implode(',', array_map('intval', $mvaData)) . ')'
                : '()';
        }

        return [$fieldList, $valueList];
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
     * 通用批量写入方法：每块一条多行 REPLACE，向量由引擎 Auto Embeddings 自动生成
     *
     * 引擎对一条多行语句只调用一次向量化接口（实测 30 行 ≈ 0.9s），
     * 多行语句失败是原子的；整块失败时回退逐行 upsertVector，
     * 使单条坏行不毒化整批、且失败按真实主键记入重试表。
     *
     * @param string $type 类型: msg/file/task/project/user
     * @param array $rows 行数据数组（与 upsertVector 的 $data 同构，需含 id 与主键）
     * @return int 成功写入的数量
     */
    public static function batchUpsertVectors(string $type, array $rows): int
    {
        if (empty($rows) || !isset(self::VECTOR_TABLE_CONFIG[$type])) {
            return 0;
        }

        $config = self::VECTOR_TABLE_CONFIG[$type];
        $table = $config['table'];
        $pk = $config['pk'];
        $instance = new self();

        // 剔除无主键行；按内容长度估算分块（不在此渲染 SQL，块内惰性渲染以压低内存峰值）
        $pending = [];
        foreach ($rows as $row) {
            if (($row[$pk] ?? 0) <= 0) {
                continue;
            }
            $bytes = 64;
            foreach ($row as $value) {
                if (is_string($value)) {
                    $bytes += strlen($value);
                }
            }
            $pending[] = ['pk' => $row[$pk], 'bytes' => $bytes, 'row' => $row];
        }
        if (empty($pending)) {
            return 0;
        }

        // 分块：行数上限 + 字节预算（文件内容可达 10 万字符/行）
        $chunks = [];
        $current = [];
        $currentBytes = 0;
        foreach ($pending as $item) {
            if (!empty($current)
                && (count($current) >= self::BATCH_CHUNK_ROWS || $currentBytes + $item['bytes'] > self::BATCH_CHUNK_BYTES)) {
                $chunks[] = $current;
                $current = [];
                $currentBytes = 0;
            }
            $current[] = $item;
            $currentBytes += $item['bytes'];
        }
        $chunks[] = $current;

        $successCount = 0;
        foreach ($chunks as $chunk) {
            // 块内渲染，执行后即释放，内存峰值 = 原始行 + 单块 SQL
            $fieldListRef = null;
            $valuesSql = [];
            foreach ($chunk as $item) {
                [$fieldList, $valueList] = $instance->buildRowValues($config, $item['row']);
                $fieldListRef = $fieldList;
                $valuesSql[] = '(' . implode(', ', $valueList) . ')';
            }
            $sql = "REPLACE INTO {$table} (" . implode(', ', $fieldListRef) . ") VALUES "
                . implode(', ', $valuesSql);
            unset($valuesSql);
            $ok = $instance->executeRaw($sql);
            unset($sql);
            if ($ok) {
                $successCount += count($chunk);
                ManticoreSyncFailure::removeSuccessBatch($type, array_column($chunk, 'pk'), 'sync');
            } else {
                // 整块失败：回退逐行（REPLACE 幂等，重复写已生效行无害）
                foreach ($chunk as $item) {
                    if (self::upsertVector($type, $item['row'])) {
                        $successCount++;
                    }
                }
            }
        }

        return $successCount;
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

