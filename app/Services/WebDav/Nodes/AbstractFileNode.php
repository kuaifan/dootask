<?php

namespace App\Services\WebDav\Nodes;

use App\Exceptions\ApiException;
use App\Models\File;
use App\Models\User;
use App\Services\FileSystem\FileSystemService;
use App\Services\WebDav\WebDavExceptionMapper;

trait AbstractFileNode
{
    protected FileSystemService $files;
    protected User $user;
    protected File $file;
    protected string $davName;
    protected string $scope;

    protected function initializeNode(
        FileSystemService $files,
        User $user,
        File $file,
        ?string $davName = null,
        string $scope = 'files'
    ): void {
        $this->files = $files;
        $this->user = $user;
        $this->file = $file;
        $this->davName = $davName ?? $files->fullName($file);
        $this->scope = $scope;
    }

    public function getName()
    {
        return $this->davName;
    }

    public function getLastModified()
    {
        return $this->file->updated_at?->timestamp;
    }

    public function setName($name)
    {
        if ($this->scope === 'shared' && intval($this->file->pshare) === intval($this->file->id)) {
            throw WebDavExceptionMapper::map(new ApiException('共享根目录不可写'));
        }
        try {
            $this->file = $this->files->rename($this->user, $this->file, $name);
            $this->davName = $name;
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function delete()
    {
        try {
            $this->files->delete($this->user, $this->file);
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function getFileModel(): File
    {
        return $this->file;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
