<?php

namespace App\Services\WebDav;

use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Exception\InsufficientStorage;

class WebDavCopyGuardPlugin extends ServerPlugin
{
    private Server $server;

    public function initialize(Server $server)
    {
        $this->server = $server;
        $server->on('beforeCopy', [$this, 'beforeCopy'], 50);
    }

    public function getPluginName()
    {
        return 'dootask-copy-guard';
    }

    public function beforeCopy(string $sourcePath): void
    {
        $remaining = WebDavConfig::get()['copy_max_nodes'];
        $this->countNode($this->server->tree->getNodeForPath($sourcePath), $remaining);
    }

    private function countNode(INode $node, int &$remaining): void
    {
        if (--$remaining < 0) {
            throw new InsufficientStorage('复制的文件和文件夹数量超过限制');
        }
        if (!$node instanceof ICollection) {
            return;
        }
        foreach ($node->getChildren() as $child) {
            $this->countNode($child, $remaining);
        }
    }
}
