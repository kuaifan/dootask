<?php

namespace App\Module\Manticore;

use App\Models\File;
use App\Models\FileContent;
use App\Models\FileUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\TextExtractor;
use Illuminate\Support\Facades\Log;

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
 * 3. 权限更新方法
 *    - 更新权限: updateAllowedUsers($fileId);
 *
 * 4. 工具方法
 *    - 清空索引: clear();
 */
class ManticoreFile
{
    /**
     * 可搜索的文件类型
     */
    public const SEARCHABLE_TYPES = ['document', 'word', 'excel', 'ppt', 'txt', 'md', 'text', 'code'];

    /**
     * 最大内容长度（字符）- 提取后的文本内容限制（服务全文检索范围）。
     * 注意：向量化输入在 ai 插件侧另有 30000 字符上限（main.py _EMBEDDING_INPUT_MAX_CHARS），
     * 超出部分只参与全文检索、不参与语义向量。
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

        if (!Apps::isInstalled("search")) {
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
                    $embedding = ManticoreBase::getEmbedding($keyword);
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
                    $embedding = ManticoreBase::getEmbedding($keyword);
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
    // 权限计算方法
    // ==============================

    /**
     * 获取文件的 allowed_users 列表
     * 
     * 有权限查看此文件的用户列表：
     * - 文件所有者 (userid)
     * - 共享用户（FileUser 表中的 userid）
     * - userid=0 表示公开共享
     *
     * @param File $file 文件模型
     * @return array 有权限的用户ID数组
     */
    public static function getAllowedUsers(File $file): array
    {
        $userids = [$file->userid]; // 所有者

        // 获取共享用户（包括 userid=0 表示公开）
        $shareUsers = FileUser::where('file_id', $file->id)
            ->pluck('userid')
            ->toArray();

        return array_unique(array_merge($userids, $shareUsers));
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 同步单个文件到 Manticore（含 allowed_users，向量由引擎 Auto Embeddings 自动生成）
     *
     * @param File $file 文件模型
     * @return bool 是否成功
     */
    public static function sync(File $file): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        // 不处理文件夹
        if ($file->type === 'folder') {
            return true;
        }

        // 根据文件类型检查大小限制
        $maxSize = self::getMaxFileSizeByExt($file->ext);
        if ($file->size > $maxSize) {
            // 删除可能存在的旧索引（文件更新后可能超限）
            self::delete($file->id);
            return true;
        }

        try {
            return ManticoreBase::upsertFileVector(self::buildRow($file));
        } catch (\Exception $e) {
            Log::error('Manticore sync error: ' . $e->getMessage(), [
                'file_id' => $file->id,
                'file_name' => $file->name,
            ]);
            return false;
        }
    }

    /**
     * 构建文件索引行数据（含提取的文件内容与 allowed_users）
     *
     * @param File $file 文件模型
     * @return array 行数据
     */
    private static function buildRow(File $file): array
    {
        // 提取文件内容并限制长度
        $content = mb_substr(self::extractFileContent($file), 0, self::MAX_CONTENT_LENGTH);

        return [
            'id' => $file->id,
            'file_id' => $file->id,
            'userid' => $file->userid,
            'pshare' => $file->pshare ?? 0,
            'file_name' => $file->name,
            'file_type' => $file->type,
            'file_ext' => $file->ext,
            'content' => $content,
            'allowed_users' => self::getAllowedUsers($file),
        ];
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
     * 批量同步文件（每块一条多行 REPLACE，向量由引擎自动生成）
     *
     * @param iterable $files 文件列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $files): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        $rows = [];
        foreach ($files as $file) {
            // 文件夹不索引
            if ($file->type === 'folder') {
                $count++;
                continue;
            }
            // 超限文件删除旧索引
            if ($file->size > self::getMaxFileSizeByExt($file->ext)) {
                self::delete($file->id);
                $count++;
                continue;
            }
            try {
                $rows[] = self::buildRow($file);
            } catch (\Exception $e) {
                Log::error('Manticore file batchSync build error: ' . $e->getMessage(), [
                    'file_id' => $file->id,
                ]);
            }
        }

        return $count + ManticoreBase::batchUpsertVectors('file', $rows);
    }

    /**
     * 删除文件索引
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function delete(int $fileId): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        return ManticoreBase::deleteFileVector($fileId);
    }

    /**
     * 提取文件内容（支持分页）
     *
     * @param File|string $fileOrPath 文件模型 或 文件路径/URL
     * @param int $offset 起始位置（字符数），默认 0
     * @param int $limit 获取长度（字符数），默认 50000，最大 200000
     * @return array 包含 content, total_length, offset, limit, has_more, 或 error
     */
    public static function extractFileContentPaginated(File|string $fileOrPath, int $offset = 0, int $limit = 50000): array
    {
        $offset = max(0, $offset);
        $limit = min(max(1, $limit), 200000);

        // 根据参数类型获取完整内容
        if ($fileOrPath instanceof File) {
            if ($fileOrPath->type === 'folder') {
                return ['error' => '文件夹无法提取内容'];
            }
            $fullContent = self::extractFileContent($fileOrPath);
        } else {
            $fullContent = self::extractFileContentFromPath($fileOrPath);
            if (is_array($fullContent)) {
                return $fullContent; // 返回错误信息
            }
        }

        if (empty($fullContent)) {
            return ['error' => '无法提取文件内容'];
        }

        // 分页处理
        $totalLength = mb_strlen($fullContent);

        if ($offset >= $totalLength) {
            return [
                'content' => '',
                'total_length' => $totalLength,
                'offset' => $offset,
                'limit' => $limit,
                'has_more' => false,
            ];
        }

        $content = mb_substr($fullContent, $offset, $limit);
        $hasMore = ($offset + mb_strlen($content)) < $totalLength;

        return [
            'content' => $content,
            'total_length' => $totalLength,
            'offset' => $offset,
            'limit' => $limit,
            'has_more' => $hasMore,
        ];
    }

    /**
     * 通过路径/URL 提取完整内容
     * @return string|array 内容字符串，或错误数组
     */
    private static function extractFileContentFromPath(string $pathOrUrl): string|array
    {
        // 从 URL 中提取相对路径
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            $parsed = parse_url($pathOrUrl);
            $pathOrUrl = ltrim($parsed['path'] ?? '', '/');
        }
        if (preg_match('/^.*?(uploads\/.*)$/', $pathOrUrl, $matches)) {
            $pathOrUrl = $matches[1];
        }

        // 安全检查：只允许 uploads 目录
        if (!str_starts_with($pathOrUrl, 'uploads/')) {
            return ['error' => '不支持的文件路径'];
        }

        return self::extractFromPath($pathOrUrl);
    }

    /**
     * 提取文件内容（内部使用，返回完整内容）
     *
     * @param File $file 文件模型
     * @return string 文件内容文本
     */
    private static function extractFileContent(File $file): string
    {
        // 1. 先尝试从 FileContent 的 text 字段获取（已提取的文本内容）
        $fileContent = FileContent::where('fid', $file->id)->orderByDesc('id')->first();
        if (!$fileContent) {
            return '';
        }
        if (!empty($fileContent->text)) {
            return $fileContent->text;
        }

        // 2. 尝试从 FileContent 的 content 字段获取
        if (!empty($fileContent->content)) {
            $contentData = Base::json2array($fileContent->content);

            // 2.1 某些文件类型直接存储内容
            if (!empty($contentData['content']) && is_string($contentData['content'])) {
                return $contentData['content'];
            }

            // 2.2 通过路径提取
            $filePath = $contentData['url'] ?? null;
            if ($filePath && str_starts_with($filePath, 'uploads/')) {
                $result = self::extractFromPath($filePath);
                if (is_string($result)) {
                    return $result;
                }
            }
        }

        return '';
    }

    /**
     * 从文件路径提取内容（核心方法）
     * @return string|array 内容字符串，或错误数组
     */
    private static function extractFromPath(string $relativePath): string|array
    {
        $fullPath = public_path($relativePath);
        if (!file_exists($fullPath)) {
            return ['error' => '文件不存在'];
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $maxFileSize = self::getMaxFileSizeByExt($ext);

        $result = TextExtractor::extractFile(
            $fullPath,
            (int) ($maxFileSize / 1024),
            (int) (self::MAX_CONTENT_LENGTH / 1024)
        );

        if (!Base::isSuccess($result)) {
            return ['error' => $result['msg'] ?? '无法提取文件内容'];
        }

        return $result['data'] ?? '';
    }


    /**
     * 清空所有索引
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("search")) {
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
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        return ManticoreBase::getIndexedFileCount();
    }

    // ==============================
    // 权限更新方法
    // ==============================

    /**
     * 更新文件的 allowed_users 权限列表
     * 从 MySQL 获取最新的共享用户并更新到 Manticore
     *
     * @param int $fileId 文件ID
     * @return bool 是否成功
     */
    public static function updateAllowedUsers(int $fileId): bool
    {
        if (!Apps::isInstalled("search") || $fileId <= 0) {
            return false;
        }

        try {
            $file = File::find($fileId);
            if (!$file) {
                return false;
            }

            $userids = self::getAllowedUsers($file);
            return ManticoreBase::updateFileAllowedUsers($fileId, $userids);
        } catch (\Exception $e) {
            Log::error('Manticore updateAllowedUsers error: ' . $e->getMessage(), ['file_id' => $fileId]);
            return false;
        }
    }
}
