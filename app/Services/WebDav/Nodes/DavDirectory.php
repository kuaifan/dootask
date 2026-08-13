<?php

namespace App\Services\WebDav\Nodes;

use App\Exceptions\ApiException;
use App\Models\File as FileModel;
use App\Models\User;
use App\Services\FileSystem\FileSystemService;
use Sabre\DAV\Collection;
use App\Services\WebDav\WebDavExceptionMapper;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;

class DavDirectory extends Collection implements IMoveTarget
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

    public function getChildren()
    {
        try {
            return $this->files->children($this->user, $this->file)
                ->map(fn(FileModel $file) => NodeFactory::make($this->files, $this->user, $file, null, $this->scope))
                ->all();
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function getChild($name)
    {
        try {
            $file = $this->files->child($this->user, $this->file, $name);
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
        if (!$file) {
            throw new NotFound('File not found');
        }
        return NodeFactory::make($this->files, $this->user, $file, null, $this->scope);
    }

    public function childExists($name)
    {
        try {
            return $this->files->child($this->user, $this->file, $name) !== null;
        } catch (ApiException) {
            return false;
        }
    }

    public function createDirectory($name)
    {
        try {
            $this->files->createDirectory($this->user, $this->file, $name, $this->scope);
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function createFile($name, $data = null)
    {
        try {
            $file = $this->files->createFile($this->user, $this->file, $name, $data, $this->scope);
            return $this->files->etag($file);
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function moveInto($targetName, $sourcePath, INode $sourceNode)
    {
        if (!method_exists($sourceNode, 'getFileModel')) {
            return false;
        }
        try {
            $this->files->move(
                $this->user,
                $sourceNode->getFileModel(),
                $this->file,
                $targetName,
                $this->scope
            );
            return true;
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }
}
