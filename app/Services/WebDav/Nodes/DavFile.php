<?php

namespace App\Services\WebDav\Nodes;

use App\Exceptions\ApiException;
use App\Models\File as FileModel;
use App\Models\User;
use App\Services\FileSystem\FileSystemService;
use App\Services\WebDav\WebDavExceptionMapper;
use Sabre\DAV\File;

class DavFile extends File
{
    use AbstractFileNode;

    public function __construct(
        FileSystemService $files,
        User $user,
        FileModel $file,
        ?string $davName = null,
        string $scope = 'files'
    ) {
        $this->initializeNode($files, $user, $file, $davName, $scope);
    }

    public function put($data)
    {
        try {
            $this->file = $this->files->replaceFile($this->user, $this->file, $data);
            return $this->getETag();
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function get()
    {
        try {
            FileModel::permissionFind($this->file->id, $this->user, 0);
            return $this->files->open($this->file);
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function getSize()
    {
        return intval($this->file->size);
    }

    public function getETag()
    {
        return $this->files->etag($this->file);
    }

    public function getContentType()
    {
        return $this->files->mime($this->file);
    }
}
