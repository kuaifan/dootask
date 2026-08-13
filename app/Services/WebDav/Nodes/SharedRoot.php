<?php

namespace App\Services\WebDav\Nodes;

use App\Models\File;
use App\Models\User;
use App\Services\FileSystem\FileSystemService;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;

class SharedRoot extends Collection
{
    public function __construct(private FileSystemService $files, private User $user)
    {
    }

    public function getName() { return 'shared'; }
    public function getLastModified() { return null; }

    public function getChildren()
    {
        return $this->files->children($this->user, null, 'shared')
            ->map(fn(File $file) => NodeFactory::make(
                $this->files,
                $this->user,
                $file,
                $this->sharedName($file),
                'shared'
            ))->all();
    }

    public function getChild($name)
    {
        if (!preg_match('/ \[#(\d+)\](\.[^.]*)?$/u', $name, $matches)) {
            throw new NotFound('File not found');
        }
        $id = intval($matches[1]);
        $file = $this->files->children($this->user, null, 'shared')->firstWhere('id', $id);
        if (!$file || $this->sharedName($file) !== $name) {
            throw new NotFound('File not found');
        }
        return NodeFactory::make($this->files, $this->user, $file, $name, 'shared');
    }

    public function childExists($name)
    {
        try {
            $this->getChild($name);
            return true;
        } catch (NotFound) {
            return false;
        }
    }

    private function sharedName(File $file): string
    {
        return $file->ext
            ? $file->name . ' [#' . $file->id . '].' . $file->ext
            : $file->name . ' [#' . $file->id . ']';
    }
}
