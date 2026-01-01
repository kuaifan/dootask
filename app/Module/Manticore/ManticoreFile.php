<?php

namespace App\Module\Manticore;

use App\Models\File;
use App\Models\FileContent;
use App\Models\FileUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\TextExtractor;
use App\Module\AI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Manticore Search 文件搜索类
 *
 * 使用方法:
 *
 * 1. 搜索方法
 *    - 搜索文件: search($userid, $keyword, $searchType, $from, $size);
 *
 * 2. 同步方法
 *    - 单个同步: sync(File $file);
 *    - 批量同步: batchSync($files);
 *    - 删除索引: delete($fileId);
 *
 * 3. 工具方法
 *    - 清空索引: clear();
 */
class ManticoreFile
{
    /**
     * 可搜索的文件类型
     */
    public const SEARCHABLE_TYPES = ['document', 'word', 'excel', 'ppt', 'txt', 'md', 'text', 'code'];

    /**
     * 最大内容长度（字符）- 提取后的文本内容限制
     */
    public const MAX_CONTENT_LENGTH = 100000; // 100K 字符

    /**
     * 不同文件类型的最大大小限制（字节）
     */
    public const MAX_FILE_SIZE = [
        'office' => 50 * 1024 * 1024,  // 50MB - Office 文件图片占空间大但文本少
        'text'   => 5 * 1024 * 1024,   // 5MB  - 纯文本文件
        'other'  => 20 * 1024 * 1024,  // 20MB - PDF 等其他文件
    ];

    /**
     * Office 文件扩展名
     */
    public const OFFICE_EXTENSIONS = [
        'doc', 'docx', 'dot', 'dotx', 'odt', 'ott', 'rtf',
        'xls', 'xlsx', 'xlsm', 'xlt', 'xltx', 'ods', 'ots', 'csv', 'tsv',
        'ppt', 'pptx', 'pps', 'ppsx', 'odp', 'otp'
    ];

    /**
     * 纯文本文件扩展名
     */
    public const TEXT_EXTENSIONS = [
        'txt', 'md', 'text', 'log', 'json', 'xml', 'html', 'htm', 'css', 'js', 'ts',
        'php', 'py', 'java', 'c', 'cpp', 'h', 'go', 'rs', 'rb', 'sh', 'bash', 'sql',
        'yaml', 'yml', 'ini', 'conf', 'vue', 'jsx', 'tsx'
    ];

    /**
     * 搜索文件（支持全文、向量、混合搜索）
     *
     * @param int $userid 用户ID
     * @param string $keyword 搜索关键词
     * @param string $searchType 搜索类型: text/vector/hybrid
     * @param int $from 起始位置
     * @param int $size 返回数量
     * @return array 搜索结果
     */
    public static function search(int $userid, string $keyword, string $searchType = 'hybrid', int $from = 0, int $size = 20): array
    {
        if (empty($keyword)) {
            return [];
        }

        if (!Apps::isInstalled("manticore")) {
            // 未安装 Manticore，降级到 MySQL LIKE 搜索
            return self::searchByMysql($userid, $keyword, $from, $size);
        }

        try {
            switch ($searchType) {
                case 'text':
                    // 纯全文搜索
                    return self::formatSearchResults(
                        ManticoreBase::fullTextSearch($keyword, $userid, $size, $from)
                    );

                case 'vector':
                    // 纯向量搜索（需要先获取 embedding）
                    $embedding = self::getEmbedding($keyword);
                    if (empty($embedding)) {
                        // embedding 获取失败，降级到全文搜索
                        return self::formatSearchResults(
                            ManticoreBase::fullTextSearch($keyword, $userid, $size, $from)
                        );
                    }
                    return self::formatSearchResults(
                        ManticoreBase::vectorSearch($embedding, $userid, $size)
                    );

                case 'hybrid':
                default:
                    // 混合搜索
                    $embedding = self::getEmbedding($keyword);
                    return self::formatSearchResults(
                        ManticoreBase::hybridSearch($keyword, $embedding, $userid, $size)
                    );
            }
        } catch (\Exception $e) {
            Log::error('Manticore search error: ' . $e->getMessage());
            return self::searchByMysql($userid, $keyword, $from, $size);
        }
    }

    /**
     * 获取文本的 Embedding 向量
     *
     * @param string $text 文本
     * @return array 向量数组（空数组表示失败）
     */
    private static function getEmbedding(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        try {
            // 调用 AI 模块获取 embedding
            $result = AI::getEmbedding($text);
            if (Base::isSuccess($result)) {
                return $result['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Get embedding error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * 格式化搜索结果
     *
     * @param array $results Manticore 返回的结果
     * @return array 格式化后的结果
     */
    private static function formatSearchResults(array $results): array
    {
        $formatted = [];
        foreach ($results as $item) {
            $formatted[] = [
                'id' => $item['file_id'],
                'file_id' => $item['file_id'],
                'name' => $item['file_name'],
                'type' => $item['file_type'],
                'ext' => $item['file_ext'],
                'userid' => $item['userid'],
                'content_preview' => isset($item['content']) ? mb_substr($item['content'], 0, 500) : null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    /**
     * MySQL 降级搜索（仅搜索文件名）
     *
     * @param int $userid 用户ID
     * @param string $keyword 关键词
     * @param int $from 起始位置
     * @param int $size 返回数量
     * @return array 搜索结果
     */
    private static function searchByMysql(int $userid, string $keyword, int $from, int $size): array
    {
        // 搜索用户自己的文件
        $builder = File::where('userid', $userid)
            ->where('name', 'like', "%{$keyword}%")
            ->where('type', '!=', 'folder');

        $results = $builder->skip($from)->take($size)->get();

        return $results->map(function ($file) {
            return [
                'id' => $file->id,
                'file_id' => $file->id,
                'name' => $file->name,
                'type' => $file->type,
                'ext' => $file->ext,
                'userid' => $file->userid,
                'content_preview' => null,
                'relevance' => 0,
            ];
        })->toArray();
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 同步单个文件到 Manticore
     *
     * @param File $file 文件模型
     * @return bool 是否成功
     */
    public static function sync(File $file): bool
    {
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        // 不处理文件夹
        if ($file->type === 'folder') {
            return true;
        }

        // 根据文件类型检查大小限制
        $maxSize = self::getMaxFileSizeByExt($file->ext);
        if ($file->size > $maxSize) {
            Log::info("Manticore: Skip large file {$file->id} ({$file->size} bytes, max: {$maxSize})");
            return true;
        }

        try {
            // 提取文件内容
            $content = self::extractFileContent($file);

            // 限制提取后的内容长度
            $content = mb_substr($content, 0, self::MAX_CONTENT_LENGTH);

            // 获取 embedding（如果有内容且 AI 可用）
            $embedding = null;
            if (!empty($content) && Apps::isInstalled('ai')) {
                $embeddingResult = self::getEmbedding($content);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 写入 Manticore
            $result = ManticoreBase::upsertFileVector([
                'file_id' => $file->id,
                'userid' => $file->userid,
                'pshare' => $file->pshare ?? 0,
                'file_name' => $file->name,
                'file_type' => $file->type,
                'file_ext' => $file->ext,
                'content' => $content,
                'content_vector' => $embedding,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Manticore sync error: ' . $e->getMessage(), [
                'file_id' => $file->id,
                'file_name' => $file->name,
            ]);
            return false;
        }
    }

    /**
     * 根据文件扩展名获取最大文件大小限制
     *
     * @param string|null $ext 文件扩展名
     * @return int 最大文件大小（字节）
     */
    private static function getMaxFileSizeByExt(?string $ext): int
    {
        $ext = strtolower($ext ?? '');

        if (in_array($ext, self::OFFICE_EXTENSIONS)) {
            return self::MAX_FILE_SIZE['office'];
        }

        if (in_array($ext, self::TEXT_EXTENSIONS)) {
            return self::MAX_FILE_SIZE['text'];
        }

        return self::MAX_FILE_SIZE['other'];
    }

    /**
     * 获取所有文件类型中的最大文件大小限制
     *
     * @return int 最大文件大小（字节）
     */
    public static function getMaxFileSize(): int
    {
        return max(self::MAX_FILE_SIZE);
    }

    /**
     * 批量同步文件
     *
     * @param iterable $files 文件列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $files): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        foreach ($files as $file) {
            if (self::sync($file)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 删除文件索引
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function delete(int $fileId): bool
    {
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        return ManticoreBase::deleteFileVector($fileId);
    }

    /**
     * 提取文件内容
     *
     * @param File $file 文件模型
     * @return string 文件内容文本
     */
    private static function extractFileContent(File $file): string
    {
        // 1. 先尝试从 FileContent 的 text 字段获取（已提取的文本内容）
        $fileContent = FileContent::where('fid', $file->id)->orderByDesc('id')->first();
        if ($fileContent && !empty($fileContent->text)) {
            return $fileContent->text;
        }

        // 2. 尝试从 FileContent 的 content 字段获取
        if ($fileContent && !empty($fileContent->content)) {
            $contentData = Base::json2array($fileContent->content);

            // 2.1 某些文件类型直接存储内容
            if (!empty($contentData['content'])) {
                return is_string($contentData['content']) ? $contentData['content'] : '';
            }

            // 2.2 尝试使用 TextExtractor 提取文件内容
            $filePath = $contentData['url'] ?? null;
            if ($filePath && str_starts_with($filePath, 'uploads/')) {
                $fullPath = public_path($filePath);
                if (file_exists($fullPath)) {
                    // 根据文件类型设置不同的大小限制
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                    $maxFileSize = self::getMaxFileSizeByExt($ext);
                    $maxContentSize = self::MAX_CONTENT_LENGTH;

                    $result = TextExtractor::extractFile(
                        $fullPath,
                        (int) ($maxFileSize / 1024),     // 转换为 KB
                        (int) ($maxContentSize / 1024)   // 转换为 KB
                    );
                    if (Base::isSuccess($result)) {
                        return $result['data'] ?? '';
                    }
                }
            }
        }

        return '';
    }

    /**
     * 清空所有索引
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        return ManticoreBase::clearAllFileVectors();
    }

    /**
     * 获取已索引文件数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        return ManticoreBase::getIndexedFileCount();
    }

    // ==============================
    // 文件用户关系同步方法
    // ==============================

    /**
     * 同步单个文件的用户关系到 Manticore
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function syncFileUsers(int $fileId): bool
    {
        if (!Apps::isInstalled("manticore") || $fileId <= 0) {
            return false;
        }

        try {
            // 从 MySQL 获取文件的用户关系
            $users = FileUser::where('file_id', $fileId)
                ->select(['userid', 'permission'])
                ->get()
                ->map(function ($item) {
                    return [
                        'userid' => $item->userid,
                        'permission' => $item->permission,
                    ];
                })
                ->toArray();

            // 同步到 Manticore
            return ManticoreBase::syncFileUsers($fileId, $users);
        } catch (\Exception $e) {
            Log::error('Manticore syncFileUsers error: ' . $e->getMessage(), ['file_id' => $fileId]);
            return false;
        }
    }

    /**
     * 添加文件用户关系到 Manticore
     *
     * @param int $fileId 文件ID
     * @param int $userid 用户ID
     * @param int $permission 权限
     * @return bool 是否成功
     */
    public static function addFileUser(int $fileId, int $userid, int $permission = 0): bool
    {
        if (!Apps::isInstalled("manticore") || $fileId <= 0) {
            return false;
        }

        return ManticoreBase::upsertFileUser($fileId, $userid, $permission);
    }

    /**
     * 删除文件用户关系
     *
     * @param int $fileId 文件ID
     * @param int|null $userid 用户ID，null 表示删除所有
     * @return bool 是否成功
     */
    public static function removeFileUser(int $fileId, ?int $userid = null): bool
    {
        if (!Apps::isInstalled("manticore") || $fileId <= 0) {
            return false;
        }

        if ($userid === null) {
            return ManticoreBase::deleteFileUsers($fileId);
        }

        return ManticoreBase::deleteFileUser($fileId, $userid);
    }

    /**
     * 批量同步所有文件用户关系（全量同步）
     *
     * @param callable|null $progressCallback 进度回调
     * @return int 同步数量
     */
    public static function syncAllFileUsers(?callable $progressCallback = null): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        $lastId = 0;
        $batchSize = 1000;

        // 先清空 Manticore 中的 file_users 表
        ManticoreBase::clearAllFileUsers();

        // 分批同步
        while (true) {
            $records = FileUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                ManticoreBase::upsertFileUser($record->file_id, $record->userid, $record->permission);
                $count++;
                $lastId = $record->id;
            }

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        return $count;
    }

    /**
     * 增量同步文件用户关系（只同步新增的）
     *
     * @param callable|null $progressCallback 进度回调
     * @return int 同步数量
     */
    public static function syncFileUsersIncremental(?callable $progressCallback = null): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        $batchSize = 1000;
        $lastKey = "sync:manticoreFileUserLastId";
        $lastId = intval(ManticoreKeyValue::get($lastKey, 0));

        // 分批同步新增的记录
        while (true) {
            $records = FileUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                ManticoreBase::upsertFileUser($record->file_id, $record->userid, $record->permission);
                $count++;
                $lastId = $record->id;
            }

            // 保存进度
            ManticoreKeyValue::set($lastKey, $lastId);

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        return $count;
    }
}

