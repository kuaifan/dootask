<?php

namespace App\Services\FileSystem;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\File;
use App\Models\FileContent;
use App\Models\User;
use App\Module\Base;
use App\Services\WebDav\WebDavConfig;

class FileSystemService
{
    public function children(User $actor, ?File $parent, string $scope = 'files')
    {
        if ($parent) {
            File::permissionFind($parent->id, $actor, 0);
            return File::wherePid($parent->id)->orderBy('type')->orderBy('name')->get();
        }
        if ($scope === 'files') {
            return File::wherePid(0)->whereUserid($actor->userid)->orderBy('type')->orderBy('name')->get();
        }

        $userids = $actor->isTemp() ? [$actor->userid] : [0, $actor->userid];
        $received = File::select('files.*')
            ->join('file_users', 'files.id', '=', 'file_users.file_id')
            ->where('files.userid', '!=', $actor->userid)
            ->whereIn('file_users.userid', $userids)
            ->where('files.share', 1)
            ->distinct()
            ->orderBy('files.name')
            ->get();
        $owned = File::wherePid(0)
            ->whereUserid($actor->userid)
            ->whereShare(1)
            ->orderBy('name')
            ->get();
        return $received->concat($owned)->unique('id')->values();
    }

    public function child(User $actor, ?File $parent, string $fullName, string $scope = 'files'): ?File
    {
        if ($parent) {
            File::permissionFind($parent->id, $actor, 0);
            return $this->findByFullName(File::wherePid($parent->id), $fullName);
        }
        if ($scope === 'files') {
            return $this->findByFullName(
                File::wherePid(0)->whereUserid($actor->userid),
                $fullName
            );
        }
        return null;
    }

    public function createDirectory(User $actor, ?File $parent, string $name, string $scope = 'files'): File
    {
        $this->validateName($name);
        [$pid, $userid] = $this->target($actor, $parent, $scope);
        $this->assertCapacity($pid, $userid);
        if ($this->findByFullName(File::wherePid($pid)->whereUserid($userid), $name)) {
            throw new ApiException('文件已存在');
        }

        $file = File::createInstance([
            'pid' => $pid,
            'name' => $name,
            'type' => 'folder',
            'ext' => '',
            'userid' => $userid,
            'created_id' => $actor->userid,
        ]);
        $file->saveBeforePP();
        $this->push($file, 'add', $file);
        return $file;
    }

    public function createFile(User $actor, ?File $parent, string $fullName, $data, string $scope = 'files'): File
    {
        [$name, $ext] = $this->splitName($fullName);
        $this->validateName($fullName);
        [$pid, $userid] = $this->target($actor, $parent, $scope);
        $this->assertCapacity($pid, $userid);
        if ($this->findByFullName(File::wherePid($pid)->whereUserid($userid), $fullName)) {
            throw new ApiException('文件已存在');
        }

        $temp = $this->writeTemp($data);
        $finalPath = null;
        try {
            $file = AbstractModel::transaction(function () use ($actor, $pid, $userid, $name, $ext, $temp, &$finalPath) {
                $file = File::createInstance([
                    'pid' => $pid,
                    'name' => $name,
                    'type' => $this->typeFromExtension($ext),
                    'ext' => $ext,
                    'size' => filesize($temp),
                    'hash' => md5_file($temp),
                    'userid' => $userid,
                    'created_id' => $actor->userid,
                ]);
                $file->saveBeforePP();
                $finalPath = $this->moveToContentPath($temp, $file);
                $this->createContent($file, $actor, $finalPath);
                return $file->fresh();
            });
        } catch (\Throwable $e) {
            $this->cleanup($temp, $finalPath);
            throw $e;
        }
        $this->push($file, 'add', $file);
        return $file;
    }

    public function replaceFile(User $actor, File $file, $data): File
    {
        File::permissionFind($file->id, $actor, 1);
        if ($file->type === 'folder') {
            throw new ApiException('文件夹不能写入内容');
        }
        $temp = $this->writeTemp($data);
        $finalPath = null;
        try {
            $file = AbstractModel::transaction(function () use ($actor, $file, $temp, &$finalPath) {
                $locked = File::whereId($file->id)->lockForUpdate()->first();
                if (!$locked) {
                    throw new ApiException('文件不存在或已被删除');
                }
                File::permissionFind($locked->id, $actor, 1);
                $locked->size = filesize($temp);
                $locked->hash = md5_file($temp);
                $locked->updated_at = now();
                $locked->save();
                $finalPath = $this->moveToContentPath($temp, $locked);
                $this->createContent($locked, $actor, $finalPath);
                return $locked->fresh();
            });
        } catch (\Throwable $e) {
            $this->cleanup($temp, $finalPath);
            throw $e;
        }
        $this->push($file, 'content');
        return $file;
    }

    public function overwriteByMove(User $actor, File $source, File $destination): File
    {
        File::permissionFind($source->id, $actor, 1000);
        $destination = $this->overwriteByCopy($actor, $source, $destination);
        $this->delete($actor, $source);
        return $destination;
    }

    public function overwriteByCopy(User $actor, File $source, File $destination): File
    {
        File::permissionFind($source->id, $actor, 0);
        File::permissionFind($destination->id, $actor, 1);
        if ($source->type === 'folder' || $destination->type === 'folder') {
            throw new ApiException('文件夹不能写入内容');
        }

        $stream = $this->open($source);
        try {
            $destination = $this->replaceFile($actor, $destination, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        return $destination;
    }

    public function rename(User $actor, File $file, string $fullName): File
    {
        File::permissionFind($file->id, $actor, 1);
        return $this->renameFile($file, $fullName);
    }

    public function renameConflictAsAdmin(User $actor, File $file, string $fullName): File
    {
        if (!$actor->isAdmin()) {
            throw new ApiException('仅限管理员操作');
        }
        return $this->renameFile($file, $fullName);
    }

    private function renameFile(File $file, string $fullName): File
    {
        $this->validateName($fullName);
        [$name, $ext] = $file->type === 'folder' ? [$fullName, ''] : $this->splitName($fullName);
        $exists = File::wherePid($file->pid)->whereUserid($file->userid)
            ->whereName($name)->whereExt($ext)->where('id', '!=', $file->id)->exists();
        if ($exists) {
            throw new ApiException('文件已存在');
        }
        $file->name = $name;
        $file->ext = $ext;
        if ($file->type !== 'folder') {
            $file->type = $this->typeFromExtension($ext);
        }
        $file->save();
        $this->push($file, 'update', $file);
        return $file;
    }

    public function move(User $actor, File $file, ?File $targetParent, string $targetName, string $scope): File
    {
        File::permissionFind($file->id, $actor, 1000);
        [$pid, $userid] = $this->target($actor, $targetParent, $scope);
        $this->assertCapacity($pid, $userid, $file->pid === $pid ? 1 : 0);
        if ($file->type === 'folder' && ($pid === $file->id || str_contains((string) $targetParent?->pids, ",{$file->id},"))) {
            throw new ApiException('移动位置错误');
        }
        $file->pid = $pid;
        if ($file->userid !== $userid) {
            $file->userid = $userid;
            $file->updateChildFilesUserid($userid);
        }
        $this->rename($actor, $file, $targetName);
        $file->saveBeforePP();
        return $file->fresh();
    }

    public function delete(User $actor, File $file): void
    {
        File::permissionFind($file->id, $actor, 1000);
        $file->deleteFile();
    }

    public function open(File $file)
    {
        $content = FileContent::whereFid($file->id)->orderByDesc('id')->first();
        if (!$content) {
            return fopen('php://temp', 'r+');
        }
        $data = Base::json2array($content->content ?: []);
        $relative = $data['url'] ?? '';
        $path = public_path($relative);
        if (!str_starts_with($relative, 'uploads/') || !is_file($path)) {
            throw new ApiException('文件内容不存在');
        }
        $stream = fopen($path, 'rb');
        if (!$stream) {
            throw new ApiException('文件内容读取失败');
        }
        return $stream;
    }

    public function etag(File $file): string
    {
        $version = intval(FileContent::whereFid($file->id)->max('id'));
        return '"f-' . $file->id . '-v-' . $version . '"';
    }

    public function mime(File $file): string
    {
        $map = [
            'md' => 'text/markdown', 'txt' => 'text/plain', 'json' => 'application/json',
            'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        return $map[strtolower((string) $file->ext)] ?? 'application/octet-stream';
    }

    public function fullName(File $file): string
    {
        return $file->getNameAndExt();
    }

    private function target(User $actor, ?File $parent, string $scope): array
    {
        if ($parent) {
            $row = File::permissionFind($parent->id, $actor, 1);
            if ($row->type !== 'folder') {
                throw new ApiException('目标不是文件夹');
            }
            return [$row->id, intval($row->userid)];
        }
        if ($scope !== 'files') {
            throw new ApiException('共享根目录不可写');
        }
        return [0, intval($actor->userid)];
    }

    private function assertCapacity(int $pid, int $userid, int $offset = 0): void
    {
        $query = File::wherePid($pid);
        if ($pid === 0) {
            $query->whereUserid($userid);
        }
        if ($query->count() - $offset >= 300) {
            throw new ApiException('每个文件夹里最多只能创建300个文件或文件夹');
        }
    }

    private function validateName(string $name): void
    {
        if (mb_strlen($name) < 1 || mb_strlen($name) > 200) {
            throw new ApiException('文件名称长度必须为1至200个字符');
        }
        if (preg_match('/[\\\\\/:*?"<>|\x00-\x1F]/u', $name)) {
            throw new ApiException('文件名称包含非法字符');
        }
        if ($name === '.' || $name === '..') {
            throw new ApiException('文件名称错误');
        }
    }

    private function splitName(string $fullName): array
    {
        $position = mb_strrpos($fullName, '.');
        if ($position === false || $position === 0 || $position === mb_strlen($fullName) - 1) {
            return [$fullName, ''];
        }
        return [mb_substr($fullName, 0, $position), strtolower(mb_substr($fullName, $position + 1))];
    }

    private function findByFullName($query, string $fullName): ?File
    {
        [$name, $ext] = $this->splitName($fullName);
        $file = (clone $query)->whereName($name)->whereExt($ext)->first();
        if (!$file && $ext !== '') {
            $file = (clone $query)->whereType('folder')->whereName($fullName)->first();
        }
        return $file;
    }

    private function typeFromExtension(string $ext): string
    {
        if (in_array($ext, ['md', 'markdown', 'text'], true)) return 'document';
        if ($ext === 'drawio') return 'drawio';
        if ($ext === 'mind') return 'mind';
        if (in_array($ext, ['doc', 'docx', 'dot', 'dotx', 'odt', 'ott', 'rtf'], true)) return 'word';
        if (in_array($ext, ['xls', 'xlsx', 'xlsm', 'xlt', 'xltx', 'ods', 'ots', 'csv', 'tsv'], true)) return 'excel';
        if (in_array($ext, ['ppt', 'pptx', 'pps', 'ppsx', 'pot', 'potx', 'odp', 'otp'], true)) return 'ppt';
        if (in_array($ext, File::imageExt, true) || $ext === 'svg') return 'picture';
        if (in_array($ext, File::codeExt, true)) return 'code';
        if (in_array($ext, ['rar', 'zip', 'jar', '7-zip', 'tar', 'gzip', '7z', 'gz'], true)) return 'archive';
        if (in_array($ext, ['mp3', 'wav', 'mp4', 'flv', 'avi', 'mov', 'wmv', 'mkv'], true)) return 'media';
        return match ($ext) {
            'pdf' => 'pdf', 'txt' => 'txt', 'xmind' => 'xmind', 'ofd' => 'ofd',
            'dwg', 'dxf' => 'cad', 'tif', 'tiff' => 'tif', 'wps' => 'wps',
            default => '',
        };
    }

    private function writeTemp($data): string
    {
        $dir = storage_path('app/webdav/tmp');
        Base::makeDir($dir);
        $path = $dir . '/' . bin2hex(random_bytes(16));
        $output = fopen($path, 'wb');
        if (!$output) throw new ApiException('临时文件创建失败');
        $input = is_resource($data) ? $data : null;
        $max = WebDavConfig::get()['max_file_bytes'] ?? intval(config('dootask.webdav.max_file_bytes'));
        if ($input) {
            $written = 0;
            while (!feof($input)) {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false) {
                    fclose($output);
                    @unlink($path);
                    throw new ApiException('文件读取失败');
                }
                $written += strlen($chunk);
                if ($written > $max) {
                    fclose($output);
                    @unlink($path);
                    throw new ApiException('文件大小超过限制');
                }
                fwrite($output, $chunk);
            }
        } else {
            $content = (string) $data;
            if (strlen($content) > $max) {
                fclose($output);
                @unlink($path);
                throw new ApiException('文件大小超过限制');
            }
            fwrite($output, $content);
        }
        fclose($output);
        return $path;
    }

    private function moveToContentPath(string $temp, File $file): string
    {
        $dir = 'uploads/file/' . ($file->type ?: 'other') . '/' . date('Ym') . '/' . $file->id . '/';
        Base::makeDir(public_path($dir));
        $relative = $dir . hash_file('sha256', $temp) . '-' . bin2hex(random_bytes(4));
        if (!rename($temp, public_path($relative))) {
            throw new ApiException('文件保存失败');
        }
        return $relative;
    }

    private function createContent(File $file, User $actor, string $relative): FileContent
    {
        $meta = ['from' => '', 'type' => $file->type, 'ext' => $file->ext, 'url' => $relative];
        if ($file->type === 'picture' && $size = @getimagesize(public_path($relative))) {
            $meta['width'] = $size[0];
            $meta['height'] = $size[1];
        }
        $content = FileContent::createInstance([
            'fid' => $file->id,
            'content' => $meta,
            'text' => '',
            'size' => $file->size,
            'userid' => $actor->userid,
        ]);
        $content->save();
        return $content;
    }

    private function cleanup(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if (!$path) continue;
            $absolute = str_starts_with($path, 'uploads/') ? public_path($path) : $path;
            if (is_file($absolute)) @unlink($absolute);
        }
    }

    private function push(File $file, string $action, $data = null): void
    {
        if (app()->bound('swoole')) {
            $file->pushMsg($action, $data);
        }
    }
}
