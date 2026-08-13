<?php

namespace App\Services\WebDav;

use App\Models\User;
use App\Models\WebDavCredential;
use App\Models\WebDavLock;
use Sabre\DAV\Locks\Backend\AbstractBackend;
use Sabre\DAV\Locks\LockInfo;
use Sabre\DAV\Server;

class WebDavLockBackend extends AbstractBackend
{
    public function __construct(private User $user, private WebDavCredential $credential)
    {
    }

    public function getLocks($uri, $returnChildLocks)
    {
        $prefix = $this->namespacePrefix($uri);
        $rows = WebDavLock::where('timeout_at', '>', now())
            ->where('uri', 'like', $prefix . '%')
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $lockUri = substr($row->uri, strlen($prefix));
            $isExact = $lockUri === $uri;
            $isParent = $row->depth === 'infinity' && str_starts_with($uri, rtrim($lockUri, '/') . '/');
            $isChild = $returnChildLocks && str_starts_with($lockUri, rtrim($uri, '/') . '/');
            if (!$isExact && !$isParent && !$isChild) continue;

            $lock = new LockInfo();
            $lock->owner = $row->owner;
            $lock->token = $row->token;
            $lock->timeout = max(1, $row->timeout_at->timestamp - time());
            $lock->created = $row->created_at?->timestamp ?? time();
            $lock->scope = $row->scope === 'shared' ? LockInfo::SHARED : LockInfo::EXCLUSIVE;
            $lock->depth = $row->depth === 'infinity' ? Server::DEPTH_INFINITY : 0;
            $lock->uri = $lockUri;
            $result[] = $lock;
        }
        return $result;
    }

    public function lock($uri, LockInfo $lockInfo)
    {
        $storageUri = $this->storageUri($uri);
        $timeout = intval($lockInfo->timeout);
        if ($timeout <= 0 || $timeout === LockInfo::TIMEOUT_INFINITE) {
            $timeout = intval(config('dootask.webdav.lock_timeout_seconds', 1800));
        }
        $timeout = min($timeout, intval(config('dootask.webdav.lock_max_timeout_seconds', 7200)));
        $row = WebDavLock::whereToken($lockInfo->token)->first();
        $params = [
            'userid' => $this->user->userid,
            'credential_id' => $this->credential->id,
            'uri' => $storageUri,
            'uri_hash' => hash('sha256', $storageUri),
            'owner' => mb_substr((string) $lockInfo->owner, 0, 255),
            'scope' => $lockInfo->scope === LockInfo::SHARED ? 'shared' : 'exclusive',
            'depth' => $lockInfo->depth === Server::DEPTH_INFINITY ? 'infinity' : '0',
            'timeout_at' => now()->addSeconds($timeout),
        ];
        if ($row) {
            $row->updateInstance($params);
        } else {
            $row = WebDavLock::createInstance(array_merge($params, ['token' => $lockInfo->token]));
        }
        $row->save();
        return true;
    }

    public function unlock($uri, LockInfo $lockInfo)
    {
        $storageUri = $this->storageUri($uri);
        return WebDavLock::whereUriHash(hash('sha256', $storageUri))
            ->whereToken($lockInfo->token)
            ->whereUserid($this->user->userid)
            ->delete() > 0;
    }

    private function storageUri(string $uri): string
    {
        return $this->namespacePrefix($uri) . $uri;
    }

    private function namespacePrefix(string $uri): string
    {
        $path = ltrim($uri, '/');
        return ($path === 'shared' || str_starts_with($path, 'shared/'))
            ? 'shared:'
            : 'user:' . $this->user->userid . ':';
    }
}
