<?php

namespace App\Services\WebDav;

use App\Models\User;
use App\Models\WebDavCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class WebDavAuthenticator
{
    private bool $rateLimited = false;

    public function authenticate(Request $request): ?array
    {
        $header = (string) $request->header('Authorization');
        if (!str_starts_with($header, 'Basic ')) {
            return null;
        }
        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }
        [$publicId, $secret] = explode(':', $decoded, 2);
        $key = 'webdav-auth:' . sha1($request->ip() . '|' . $publicId);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->rateLimited = true;
            return null;
        }

        $credential = WebDavCredential::wherePublicId($publicId)->first();
        $user = $credential ? User::whereUserid($credential->userid)->first() : null;
        if (!$credential || !$user || !$credential->verify($secret) || !WebDavConfig::isAllowed($user)) {
            RateLimiter::hit($key, 60);
            return null;
        }
        RateLimiter::clear($key);

        if (!$credential->last_used_at || $credential->last_used_at->lt(now()->subMinutes(5))) {
            $credential->last_used_at = now();
            $credential->last_used_ip = mb_substr((string) $request->ip(), 0, 45);
            $credential->last_user_agent = mb_substr((string) $request->userAgent(), 0, 255);
            $credential->save();
        }
        return [$user, $credential];
    }

    public function wasRateLimited(): bool
    {
        return $this->rateLimited;
    }
}
