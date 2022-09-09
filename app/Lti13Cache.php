<?php

namespace App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Packback\Lti1p3\Interfaces\ICache;

class Lti13Cache implements ICache
{
    public const NONCE_PREFIX = 'nonce_';

    public function getLaunchData(string $key): ?array
    {
        return Cache::get($key);
    }

    public function cacheLaunchData(string $key, array $jwtBody): void
    {
        $duration = Config::get('cache.duration.default');
        Cache::put($key, $jwtBody, $duration);
    }

    public function cacheNonce(string $nonce, string $state): void
    {
        $duration = Config::get('cache.duration.default');
        Cache::put(static::NONCE_PREFIX.$nonce, $state, $duration);
    }

    public function checkNonceIsValid(string $nonce, string $state): bool
    {
        return Cache::get(static::NONCE_PREFIX.$nonce, false) === $state;
    }

    public function cacheAccessToken(string $key, string $accessToken): void
    {
        $duration = Config::get('cache.duration.min');
        Cache::put($key, $accessToken, $duration);
    }

    public function getAccessToken(string $key): ?string
    {
        return Cache::has($key) ? Cache::get($key) : null;
    }

    public function clearAccessToken(string $key): void
    {
        Cache::forget($key);
    }
}
