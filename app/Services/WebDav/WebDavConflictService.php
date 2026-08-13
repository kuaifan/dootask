<?php

namespace App\Services\WebDav;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\File;
use App\Models\User;
use App\Models\WebDavOperationLog;
use App\Services\FileSystem\FileSystemService;

class WebDavConflictService
{
    public function renameAsAdmin(
        User $admin,
        int $fileId,
        string $name,
        ?string $requestId,
        ?string $ip,
        ?string $userAgent
    ): File {
        if (!$admin->isAdmin()) {
            throw new ApiException('仅限管理员操作');
        }
        if ($name === '') {
            throw new ApiException('文件名不能为空');
        }

        return AbstractModel::transaction(function () use ($admin, $fileId, $name, $requestId, $ip, $userAgent) {
            $file = File::whereId($fileId)->lockForUpdate()->first();
            if (!$file) {
                throw new ApiException('文件不存在或已被删除');
            }
            $conflictCount = File::wherePid($file->pid)
                ->whereUserid($file->userid)
                ->whereName($file->name)
                ->whereExt($file->ext)
                ->lockForUpdate()
                ->count();
            if ($conflictCount < 2) {
                throw new ApiException('文件已不在冲突组中');
            }

            $oldName = $file->getNameAndExt();
            $oldPath = WebDavConfig::filePath($file);
            $ownerId = intval($file->userid);
            $file = (new FileSystemService())->renameConflictAsAdmin($admin, $file, $name);
            WebDavOperationLog::createInstance([
                'request_id' => mb_substr((string) $requestId, 0, 100),
                'userid' => intval($admin->userid),
                'method' => 'ADMIN_RENAME',
                'uri' => mb_substr($oldPath, 0, 1000),
                'file_id' => intval($file->id),
                'status' => 200,
                'result' => mb_substr("owner={$ownerId}; {$oldName} -> {$file->getNameAndExt()}", 0, 255),
                'ip' => mb_substr((string) $ip, 0, 45),
                'user_agent' => mb_substr((string) $userAgent, 0, 255),
            ])->save();
            return $file;
        });
    }
}
