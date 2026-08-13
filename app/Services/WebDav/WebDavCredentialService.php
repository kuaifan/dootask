<?php

namespace App\Services\WebDav;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\User;
use App\Models\WebDavCredential;
use App\Models\WebDavLock;
use App\Models\WebDavOperationLog;

class WebDavCredentialService
{
    public function deleteInactive(
        User $user,
        int $credentialId,
        ?string $requestId,
        ?string $ip,
        ?string $userAgent
    ): void {
        AbstractModel::transaction(function () use ($user, $credentialId, $requestId, $ip, $userAgent) {
            $credential = WebDavCredential::whereUserid($user->userid)
                ->whereId($credentialId)
                ->lockForUpdate()
                ->first();
            if (!$credential) {
                throw new ApiException('WebDAV 应用密码不存在');
            }
            if ($credential->isActive()) {
                throw new ApiException('有效的应用密码请先撤销');
            }

            WebDavLock::whereCredentialId($credential->id)->delete();
            WebDavOperationLog::createInstance([
                'request_id' => mb_substr((string) $requestId, 0, 100),
                'userid' => intval($user->userid),
                'credential_id' => intval($credential->id),
                'method' => 'CREDENTIAL_DELETE',
                'status' => 200,
                'result' => mb_substr("public_id={$credential->public_id}; name={$credential->name}", 0, 255),
                'ip' => mb_substr((string) $ip, 0, 45),
                'user_agent' => mb_substr((string) $userAgent, 0, 255),
            ])->save();
            $credential->delete();
        });
    }
}
