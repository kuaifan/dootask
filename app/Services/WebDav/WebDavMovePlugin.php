<?php

namespace App\Services\WebDav;

use App\Exceptions\ApiException;
use App\Services\FileSystem\FileSystemService;
use App\Services\WebDav\Nodes\DavFile;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class WebDavMovePlugin extends ServerPlugin
{
    private Server $server;

    public function __construct(private FileSystemService $files)
    {
    }

    public function initialize(Server $server)
    {
        $this->server = $server;
        $server->on('method:MOVE', [$this, 'httpMove'], 50);
        $server->on('method:COPY', [$this, 'httpCopy'], 50);
    }

    public function getPluginName()
    {
        return 'dootask-move';
    }

    public function httpMove(RequestInterface $request, ResponseInterface $response): ?bool
    {
        $move = $this->server->getCopyAndMoveInfo($request);
        if (!$move['destinationExists']) {
            return null;
        }

        $sourcePath = $request->getPath();
        $source = $this->server->tree->getNodeForPath($sourcePath);
        $destination = $move['destinationNode'];
        if (!$source instanceof DavFile || !$destination instanceof DavFile) {
            return null;
        }

        if (!$this->server->emit('beforeUnbind', [$sourcePath])) {
            return false;
        }
        if (!$this->server->emit('beforeBind', [$move['destination']])) {
            return false;
        }
        if (!$this->server->emit('beforeMove', [$sourcePath, $move['destination']])) {
            return false;
        }

        try {
            $this->files->overwriteByMove(
                $source->getUser(),
                $source->getFileModel(),
                $destination->getFileModel()
            );
        } catch (ApiException $exception) {
            throw WebDavExceptionMapper::map($exception);
        }

        $this->server->emit('afterUnbind', [$sourcePath]);
        $this->server->emit('afterBind', [$move['destination']]);
        $response->setHeader('Content-Length', '0');
        $response->setStatus(204);
        return false;
    }

    public function httpCopy(RequestInterface $request, ResponseInterface $response): ?bool
    {
        $copy = $this->server->getCopyAndMoveInfo($request);
        if (!$copy['destinationExists']) {
            return null;
        }

        $sourcePath = $request->getPath();
        $source = $this->server->tree->getNodeForPath($sourcePath);
        $destination = $copy['destinationNode'];
        if (!$source instanceof DavFile || !$destination instanceof DavFile) {
            return null;
        }

        if (!$this->server->emit('beforeBind', [$copy['destination']])) {
            return false;
        }
        if (!$this->server->emit('beforeCopy', [$sourcePath, $copy['destination'], $copy['depth']])) {
            return false;
        }

        try {
            $this->files->overwriteByCopy(
                $source->getUser(),
                $source->getFileModel(),
                $destination->getFileModel()
            );
        } catch (ApiException $exception) {
            throw WebDavExceptionMapper::map($exception);
        }

        $this->server->emit('afterCopy', [$sourcePath, $copy['destination'], $copy['depth']]);
        $this->server->emit('afterBind', [$copy['destination']]);
        $response->setHeader('Content-Length', '0');
        $response->setStatus(204);
        return false;
    }
}
