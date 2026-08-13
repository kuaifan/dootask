<?php

namespace App\Services\WebDav\Nodes;

use App\Exceptions\ApiException;
use App\Models\File;
use App\Models\User;
use App\Services\FileSystem\FileSystemService;
use Sabre\DAV\Collection;
use App\Services\WebDav\WebDavExceptionMapper;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;

class FilesRoot extends Collection implements IMoveTarget
{
    public function __construct(private FileSystemService $files, private User $user)
    {
    }

    public function getName() { return 'files'; }
    public function getLastModified() { return null; }

    public function getChildren()
    {
        try {
            return $this->files->children($this->user, null, 'files')
                ->map(fn(File $file) => NodeFactory::make($this->files, $this->user, $file))
                ->all();
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function getChild($name)
    {
        try {
            $file = $this->files->child($this->user, null, $name, 'files');
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
        if (!$file) throw new NotFound('File not found');
        return NodeFactory::make($this->files, $this->user, $file);
    }

    public function childExists($name)
    {
        try {
            return $this->files->child($this->user, null, $name, 'files') !== null;
        } catch (ApiException) {
            return false;
        }
    }

    public function createDirectory($name)
    {
        try {
            $this->files->createDirectory($this->user, null, $name, 'files');
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function createFile($name, $data = null)
    {
        try {
            $file = $this->files->createFile($this->user, null, $name, $data, 'files');
            return $this->files->etag($file);
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }

    public function moveInto($targetName, $sourcePath, INode $sourceNode)
    {
        if (!method_exists($sourceNode, 'getFileModel')) return false;
        try {
            $this->files->move($this->user, $sourceNode->getFileModel(), null, $targetName, 'files');
            return true;
        } catch (ApiException $e) {
            throw WebDavExceptionMapper::map($e);
        }
    }
}
