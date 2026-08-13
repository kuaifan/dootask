<?php

namespace App\Models;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $userid
 * @property string $name
 * @property string $password_hash
 * @property string $password_suffix
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 */
class WebDavCredential extends AbstractModel
{
    protected $table = 'webdav_credentials';

    protected $hidden = ['password_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public static function issue(User $user, string $name, ?int $expireDays): array
    {
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $credential = self::createInstance([
            'public_id' => 'dtw_' . strtolower((string) Str::ulid()),
            'userid' => $user->userid,
            'name' => $name,
            'password_hash' => Hash::make($secret),
            'password_suffix' => substr($secret, -4),
            'expires_at' => $expireDays ? now()->addDays($expireDays) : null,
        ]);
        $credential->save();

        return [$credential, $secret];
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function verify(string $secret): bool
    {
        return $this->isActive() && Hash::check($secret, $this->password_hash);
    }

    public function revoke(): void
    {
        if ($this->revoked_at === null) {
            $this->revoked_at = now();
            $this->save();
        }
        WebDavLock::whereCredentialId($this->id)->delete();
    }

    public function status(): string
    {
        if ($this->revoked_at !== null) {
            return 'revoked';
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }
        return 'active';
    }
}
