<?php

namespace App\Services\WebDav;

use App\Models\User;
use App\Models\WebDavCredential;
use App\Services\FileSystem\FileSystemService;
use App\Services\WebDav\Nodes\DavRoot;
use Sabre\DAV\Locks\Plugin as LocksPlugin;
use Sabre\DAV\PropertyStorage\Plugin as PropertyStoragePlugin;
use Sabre\DAV\Server;

class WebDavServerFactory
{
    public function make(User $user, WebDavCredential $credential): Server
    {
        $files = new FileSystemService();
        $server = new Server(
            new DavRoot($files, $user),
            new WebDavSapi()
        );
        $server->setBaseUri('/dav/');
        $server->debugExceptions = false;
        Server::$exposeVersion = false;
        $server->enablePropfindDepthInfinity = false;
        $server->addPlugin(new LocksPlugin(new WebDavLockBackend($user, $credential)));
        $server->addPlugin(new PropertyStoragePlugin(new WebDavPropertyBackend($user)));
        $server->addPlugin(new WebDavMovePlugin($files));
        $server->addPlugin(new WebDavCopyGuardPlugin());
        return $server;
    }
}
