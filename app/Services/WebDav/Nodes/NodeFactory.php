<?php

namespace App\Services\WebDav\Nodes;

use App\Models\File;
use App\Models\User;
use App\Services\FileSystem\FileSystemService;

class NodeFactory
{
    public static function make(
        FileSystemService $files,
        User $user,
        File $file,
        ?string $davName = null,
        string $scope = 'files'
    ) {
        return $file->type === 'folder'
            ? new DavDirectory($files, $user, $file, $davName, $scope)
            : new DavFile($files, $user, $file, $davName, $scope);
    }
}
