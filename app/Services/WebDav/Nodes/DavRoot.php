<?php

namespace App\Services\WebDav\Nodes;

use App\Models\User;
use App\Services\FileSystem\FileSystemService;
use Sabre\DAV\SimpleCollection;

class DavRoot extends SimpleCollection
{
    public function __construct(FileSystemService $files, User $user)
    {
        parent::__construct('root', [
            new FilesRoot($files, $user),
            new SharedRoot($files, $user),
        ]);
    }
}
