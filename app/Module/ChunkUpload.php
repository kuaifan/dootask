<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\File as FileModel;
use App\Models\FileContent;
use App\Models\User;
use App\Models\WebSocketDialog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;

/**
 * 分片上传核心：状态机与磁盘/Redis 调度。
 *
 * 流程：start → receive × N → merge → (scene dispatcher) → cleanup
 *
 * Redis key:
 *   upload:{upload_id}                  → JSON 元数据  (TTL 24h)
 *   upload:{upload_id}:chunks           → SET 已收分片 index  (TTL 24h)
 *   upload:hash:{userid}:{hash}         → upload_id 反查（续传 / 同 hash 复用） (TTL 24h)
 *
 * 磁盘：
 *   uploads/tmp/chunks/{userid}/{upload_id}/{index}
 */
class ChunkUpload
{
    /** 单个分片大小（5MB）。注意：要小于 Swoole package_max_length 1G */
    const CHUNK_SIZE = 5 * 1024 * 1024;

    /** 状态/反查索引 TTL（秒）：24h */
    const STATE_TTL = 86400;

    /** 单文件硬上限（KB）：系统设置之外的兜底保护，10G */
    const MAX_FILE_KB = 10 * 1024 * 1024;

    /** 支持的 scene 枚举 */
    const SCENES = ['file_cabinet', 'dialog_file', 'image', 'generic_file'];

    /**
     * 启动上传。
     *   - 同用户同 hash 命中 files 表 → 秒传
     *   - 同用户同 hash 命中 upload 反查 → 续传
     *   - 否则新建 upload_id
     *
     * @param User $user
     * @param array $param [hash, size(B), name, scene, scene_params(array)]
     * @return array
     */
    public static function start(User $user, array $param): array
    {
        $hash = strtolower(trim($param['hash'] ?? ''));
        $size = intval($param['size'] ?? 0);
        $name = trim($param['name'] ?? '');
        $scene = trim($param['scene'] ?? '');
        $sceneParams = $param['scene_params'] ?? [];
        if (!is_array($sceneParams)) {
            $sceneParams = [];
        }

        if (strlen($hash) !== 32) {
            return Base::retError('文件 hash 格式错误');
        }
        if ($size <= 0) {
            return Base::retError('文件大小无效');
        }
        if (intval(ceil($size / 1024)) > self::MAX_FILE_KB) {
            return Base::retError('文件超过系统支持的最大尺寸');
        }
        // init 时拦截系统配置上限，避免传完分片才在 merge 阶段被 Base::upload 拒绝
        $fileUploadLimit = intval(Base::settingFind('system', 'file_upload_limit', 0));
        if ($fileUploadLimit <= 0) {
            $fileUploadLimit = 1024;
        }
        if ($size > $fileUploadLimit * 1024 * 1024) {
            return Base::retError('文件大小超限，最大限制：' . $fileUploadLimit . 'MB');
        }
        if ($name === '') {
            return Base::retError('文件名不能为空');
        }
        if (!in_array($scene, self::SCENES, true)) {
            return Base::retError('不支持的上传场景');
        }

        // 1) 秒传：同用户已上传过同 hash 文件 → 直接复用入库
        $hit = self::trySecondPass($user, $scene, $hash, $name, $sceneParams);
        if ($hit !== null) {
            return Base::retSuccess('success', $hit);
        }

        // 2) 续传：同用户同 hash 有未完成上传
        $reuseKey = self::keyHashIndex($user->userid, $hash);
        $existingId = Redis::get($reuseKey);
        if ($existingId) {
            $meta = self::loadMeta($existingId);
            if ($meta && $meta['userid'] === $user->userid && $meta['hash'] === $hash) {
                return Base::retSuccess('success', self::sessionView($existingId, $meta));
            }
            // 反查指向了已失效的 upload_id，清掉
            Redis::del($reuseKey);
        }

        // 3) 新建
        $uploadId = Base::generatePassword(32);
        $chunkCount = intval(ceil($size / self::CHUNK_SIZE));
        $meta = [
            'hash' => $hash,
            'size' => $size,
            'name' => $name,
            'scene' => $scene,
            'scene_params' => $sceneParams,
            'userid' => intval($user->userid),
            'chunk_size' => self::CHUNK_SIZE,
            'chunk_count' => $chunkCount,
            'created_at' => time(),
        ];
        Redis::setex(self::keyMeta($uploadId), self::STATE_TTL, json_encode($meta, JSON_UNESCAPED_UNICODE));
        Redis::setex($reuseKey, self::STATE_TTL, $uploadId);
        Base::makeDir(self::chunkDir($user->userid, $uploadId));

        return Base::retSuccess('success', self::sessionView($uploadId, $meta));
    }

    /**
     * 接收一个分片。
     *
     * @param User $user
     * @param string $uploadId
     * @param int $index 分片序号（0-based）
     * @param UploadedFile|null $blob
     * @return array
     */
    public static function receive(User $user, string $uploadId, int $index, $blob): array
    {
        $meta = self::loadMeta($uploadId);
        if (!$meta) {
            return Base::retError('上传会话不存在或已过期');
        }
        if ($meta['userid'] !== intval($user->userid)) {
            return Base::retError('上传会话归属错误');
        }
        if ($index < 0 || $index >= $meta['chunk_count']) {
            return Base::retError('分片序号超出范围');
        }
        if (!$blob || !$blob->isValid()) {
            return Base::retError('分片数据无效');
        }
        // 最后一片可能小于 CHUNK_SIZE，其余必须等于
        $isLast = $index === $meta['chunk_count'] - 1;
        $chunkSize = $blob->getSize();
        if (!$isLast && $chunkSize !== self::CHUNK_SIZE) {
            return Base::retError('分片大小不符合预期');
        }
        if ($isLast) {
            $expectLast = $meta['size'] - self::CHUNK_SIZE * ($meta['chunk_count'] - 1);
            if ($chunkSize !== $expectLast) {
                return Base::retError('末尾分片大小不符合预期');
            }
        }
        $dir = self::chunkDir($user->userid, $uploadId);
        Base::makeDir($dir);
        $blob->move($dir, (string)$index);
        // 记录已收 + 续期三个相关 key
        Redis::sadd(self::keyChunks($uploadId), $index);
        Redis::expire(self::keyChunks($uploadId), self::STATE_TTL);
        Redis::expire(self::keyMeta($uploadId), self::STATE_TTL);
        Redis::expire(self::keyHashIndex($user->userid, $meta['hash']), self::STATE_TTL);

        return Base::retSuccess('success', [
            'upload_id' => $uploadId,
            'received' => self::receivedList($uploadId),
        ]);
    }

    /**
     * 合并分片并入库。需要在 Lock 内调用。
     *
     * @param User $user
     * @param string $uploadId
     * @return array scene 入库返回结构（与 retSuccess/retError 对齐）
     */
    public static function merge(User $user, string $uploadId): array
    {
        $meta = self::loadMeta($uploadId);
        if (!$meta) {
            return Base::retError('上传会话不存在或已过期');
        }
        if ($meta['userid'] !== intval($user->userid)) {
            return Base::retError('上传会话归属错误');
        }
        $received = self::receivedList($uploadId);
        if (count($received) !== $meta['chunk_count']) {
            return Base::retError('分片不完整，无法合并');
        }

        return Lock::withLock("upload:merge:{$uploadId}", function () use ($user, $uploadId, $meta) {
            $dir = self::chunkDir($user->userid, $uploadId);
            $mergedPath = $dir . '/merged.' . substr($meta['hash'], 0, 8);
            $writeFp = @fopen($mergedPath, 'wb');
            if (!$writeFp) {
                return Base::retError('无法创建合并文件');
            }
            // 拼接与 md5 同步进行：一遍磁盘读完成"写文件 + 算 hash"
            $hashCtx = hash_init('md5');
            try {
                for ($i = 0; $i < $meta['chunk_count']; $i++) {
                    $partPath = $dir . '/' . $i;
                    $readFp = @fopen($partPath, 'rb');
                    if (!$readFp) {
                        return Base::retError("分片读取失败：{$i}");
                    }
                    while (!feof($readFp)) {
                        $buf = fread($readFp, 1024 * 1024);
                        if ($buf === false) {
                            fclose($readFp);
                            return Base::retError("分片读取失败：{$i}");
                        }
                        fwrite($writeFp, $buf);
                        hash_update($hashCtx, $buf);
                    }
                    fclose($readFp);
                }
            } finally {
                fclose($writeFp);
            }
            $actualHash = hash_final($hashCtx);
            if ($actualHash !== $meta['hash']) {
                @unlink($mergedPath);
                return Base::retError('文件校验失败，请重试');
            }

            // 调用 scene 入库
            $result = self::dispatch($user, $meta, $mergedPath);

            // 清理（无论成功失败都清，失败用户重新启 upload）
            self::cleanup($user->userid, $uploadId, $meta['hash']);

            return $result;
        }, 60000, 60000);
    }

    /**
     * 用户主动取消：校验归属后清理。会话不存在或归属错误一律静默成功，前端取消按钮不需要分支处理。
     */
    public static function cancelByUser(User $user, string $uploadId): void
    {
        $meta = self::loadMeta($uploadId);
        if (!$meta || intval($meta['userid'] ?? 0) !== $user->userid) {
            return;
        }
        self::cleanup($user->userid, $uploadId, $meta['hash'] ?? '');
    }

    /**
     * 清理一个 upload_id 的所有状态。
     */
    public static function cleanup(int $userid, string $uploadId, string $hash = ''): void
    {
        Redis::del(self::keyMeta($uploadId));
        Redis::del(self::keyChunks($uploadId));
        if ($hash) {
            Redis::del(self::keyHashIndex($userid, $hash));
        }
        $dir = self::chunkDir($userid, $uploadId);
        if (is_dir($dir)) {
            Base::deleteDirAndFile($dir);
        }
    }

    // ===== scene dispatcher =====

    /**
     * 把合并后的本地文件交给对应 scene 入库。
     * 返回结构对齐各 scene 老接口的 retSuccess。
     */
    protected static function dispatch(User $user, array $meta, string $mergedPath): array
    {
        $scene = $meta['scene'];
        $name = $meta['name'];
        $hash = $meta['hash'];
        $params = $meta['scene_params'] ?? [];

        switch ($scene) {
            case 'file_cabinet':
                $pid = intval($params['pid'] ?? 0);
                $webkitRelativePath = strval($params['webkit_relative_path'] ?? $name);
                $overwrite = boolval($params['overwrite'] ?? false);
                // pid 锁避免与并发上传的 handleDuplicateName / 中间目录创建竞态
                try {
                    return Lock::withLock("file:upload:{$user->userid}:{$pid}", function () use ($user, $pid, $mergedPath, $name, $webkitRelativePath, $hash, $overwrite) {
                        $result = (new FileModel)->contentUploadFromPath($user, $pid, $mergedPath, $name, $webkitRelativePath, $hash, $overwrite);
                        $outName = $result['data']['name'] ?? $name;
                        return Base::retSuccess($outName . ' 上传成功', $result['addItem']);
                    }, 120000, 120000);
                } catch (ApiException $e) {
                    return Base::retError($e->getMessage());
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Failed to acquire lock')) {
                        return Base::retError('上传繁忙，请稍后再试');
                    }
                    return Base::retError($e->getMessage());
                }

            case 'image':
                // 头像 / 系统图片 / 编辑器粘贴图片，对齐 system/imgupload
                $width = intval($params['width'] ?? 0);
                $height = intval($params['height'] ?? 0);
                $whcut = strval($params['whcut'] ?? 'percentage');
                $whcut = match ($whcut) {
                    '1' => 'cover',
                    '0' => 'contain',
                    'cover', 'contain' => $whcut,
                    default => 'percentage',
                };
                $scale = [$width ?: 2160, $height ?: 4160, $whcut];
                $imagePath = "uploads/user/picture/" . $user->userid . "/" . date("Ym") . "/";
                $data = Base::uploadFromPath([
                    "path_local" => $mergedPath,
                    "name" => $name,
                    "type" => 'image',
                    "path" => $imagePath,
                    "scale" => $scale,
                    "quality" => true,
                ]);
                if (Base::isError($data)) {
                    return $data;
                }
                return Base::retSuccess('success', $data['data']);

            case 'generic_file':
                // 编辑器粘贴文件 / 系统通用文件，对齐 system/fileupload
                $filePath = "uploads/user/file/" . $user->userid . "/" . date("Ym") . "/";
                $data = Base::uploadFromPath([
                    "path_local" => $mergedPath,
                    "name" => $name,
                    "type" => 'file',
                    "path" => $filePath,
                    "quality" => true,
                ]);
                return $data;

            case 'dialog_file':
                // 聊天发文件 + 任务附件共用同一接入（任务附件本质是任务对话流的一条消息）
                $dialogIds = $params['dialog_ids'] ?? [];
                if (!is_array($dialogIds)) {
                    $dialogIds = [$dialogIds];
                }
                $dialogIds = array_values(array_filter(array_map('intval', $dialogIds)));
                if (empty($dialogIds)) {
                    return Base::retError('dialog_ids 不能为空');
                }
                $replyId = intval($params['reply_id'] ?? 0);
                $imageAttachment = boolval($params['image_attachment'] ?? false);
                try {
                    return WebSocketDialog::sendMsgFilesFromPath($user, $dialogIds, $mergedPath, $name, $replyId, $imageAttachment);
                } catch (ApiException $e) {
                    return Base::retError($e->getMessage());
                }

            default:
                return Base::retError("scene 暂未实现：{$scene}");
        }
    }

    /**
     * 同 hash 命中则在目标位置复用源 FileContent 指向的物理文件，零字节传输。
     * 未命中返回 null 让上层走真上传。
     */
    protected static function trySecondPass(User $user, string $scene, string $hash, string $name, array $sceneParams): ?array
    {
        if ($scene !== 'file_cabinet') {
            return null;
        }
        $hit = FileModel::whereUserid($user->userid)->whereHash($hash)->whereNull('deleted_at')->first();
        if (!$hit) {
            return null;
        }
        $srcContent = FileContent::whereFid($hit->id)->orderByDesc('id')->first();
        if (!$srcContent) {
            return null;
        }
        $contentArr = is_array($srcContent->content)
            ? $srcContent->content
            : json_decode($srcContent->content, true);
        if (empty($contentArr['url'])) {
            return null;
        }
        $rawPid = intval($sceneParams['pid'] ?? 0);
        $webkitRelativePath = strval($sceneParams['webkit_relative_path'] ?? $name);
        $overwrite = boolval($sceneParams['overwrite'] ?? false);

        try {
            return Lock::withLock("file:upload:{$user->userid}:{$rawPid}", function () use ($user, $rawPid, $webkitRelativePath, $overwrite, $hit, $hash, $name, $contentArr) {
                [$pid, $userid, $addItem] = (new FileModel)->contentUploadPrep($user, $rawPid, $webkitRelativePath);

                $ext = $hit->ext;
                $bareName = Base::rightDelete($name, '.' . $ext);
                $existing = null;
                if ($overwrite) {
                    $existing = FileModel::wherePid($pid)->whereName($bareName)->whereExt($ext)->whereNull('deleted_at')->first();
                }
                if ($existing) {
                    $existing->size = $hit->size;
                    $existing->hash = $hash;
                    $existing->type = $hit->type;
                    if (!$existing->saveBeforePP()) {
                        throw new ApiException('秒传保存失败');
                    }
                    FileContent::createInstance([
                        'fid' => $existing->id,
                        'content' => $contentArr,
                        'text' => '',
                        'size' => $existing->size,
                        'userid' => $user->userid,
                    ])->save();
                    $created = FileModel::find($existing->id);
                    $overwriteFlag = 1;
                } else {
                    $newFile = FileModel::createInstance([
                        'pid' => $pid,
                        'name' => $bareName,
                        'type' => $hit->type,
                        'ext' => $ext,
                        'size' => $hit->size,
                        'hash' => $hash,
                        'userid' => $userid,
                        'created_id' => $user->userid,
                    ]);
                    $newFile->handleDuplicateName();
                    if (!$newFile->saveBeforePP()) {
                        throw new ApiException('秒传保存失败');
                    }
                    FileContent::createInstance([
                        'fid' => $newFile->id,
                        'content' => $contentArr,
                        'text' => '',
                        'size' => $newFile->size,
                        'userid' => $user->userid,
                    ])->save();
                    $created = FileModel::find($newFile->id);
                    $overwriteFlag = 0;
                }
                $created->pushMsg($overwriteFlag ? 'update' : 'add', $created);
                $data = FileModel::handleImageUrl($created->toArray());
                $data['full_name'] = $name;
                $data['overwrite'] = $overwriteFlag;
                $addItem[] = $data;
                return [
                    'done' => true,
                    'instant' => true,
                    'addItem' => $addItem,
                    'msg' => $name . ' 秒传成功',
                ];
            }, 120000, 120000);
        } catch (\Throwable $_e) {
            // 退化到真上传：错误由 dispatch 阶段权威报出，避免两条路径错误码不一致
            return null;
        }
    }

    // ===== helpers =====

    protected static function keyMeta(string $uploadId): string
    {
        return "upload:{$uploadId}";
    }

    protected static function keyChunks(string $uploadId): string
    {
        return "upload:{$uploadId}:chunks";
    }

    protected static function keyHashIndex(int $userid, string $hash): string
    {
        return "upload:hash:{$userid}:{$hash}";
    }

    protected static function chunkDir(int $userid, string $uploadId): string
    {
        return public_path("uploads/tmp/chunks/{$userid}/{$uploadId}");
    }

    protected static function loadMeta(string $uploadId): ?array
    {
        $raw = Redis::get(self::keyMeta($uploadId));
        if (!$raw) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    protected static function receivedList(string $uploadId): array
    {
        $list = Redis::smembers(self::keyChunks($uploadId)) ?: [];
        $list = array_map('intval', $list);
        sort($list);
        return $list;
    }

    protected static function sessionView(string $uploadId, array $meta): array
    {
        return [
            'done' => false,
            'upload_id' => $uploadId,
            'chunk_size' => $meta['chunk_size'],
            'chunk_count' => $meta['chunk_count'],
            'received' => self::receivedList($uploadId),
        ];
    }
}
