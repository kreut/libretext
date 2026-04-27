<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RateLimitByUser
{
    protected $limiter;

    // Cookie name and HMAC secret (store the secret in .env)
    const COOKIE_NAME = 'rl_token';
    const COOKIE_DAYS = 30;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, $maxAttempts = null, $decayMinutes = 1)
    {

        if (app()->environment('testing')) {
            return $next($request);
        }

        [$key, $maxAttempts, $response, $cookieToSet] = $this->resolveRateLimitContext($request, $maxAttempts, $decayMinutes);
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => $retryAfter,
            ], 429, [
                'Retry-After' => $retryAfter
            ]);
        }
        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->retriesLeft($key, $maxAttempts),
        ]);

        // Set the rate-limit cookie on the response if we need to mint one
        if ($cookieToSet) {
            $response->cookie(
                self::COOKIE_NAME,
                $cookieToSet,
                60 * 24 * self::COOKIE_DAYS, // minutes
                '/',
                null,
                true,   // secure (HTTPS only)
                true,   // httpOnly
                false,
                'Strict'
            );
        }

        return $response;
    }

    /**
     * Returns [$key, $maxAttempts, null, $cookieValueToSet|null]
     */
    private function resolveRateLimitContext(Request $request, $maxAttempts, $decayMinutes): array
    {
        // Branch 1: Logged-in user — no cookie needed, just rate-limit by user ID
        if ($request->user()) {
            $maxAttempts = $maxAttempts ?? 1000;
            $key = 'user:' . $request->user()->id;
            return [$key, $maxAttempts, null, null];
        }

        // Branch 2: Unauthenticated — check for a valid signed cookie
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        $cookieToSet = null;

        if ($cookieValue && $this->isValidCookie($cookieValue)) {
            // Valid cookie — more generous limit
            $maxAttempts = $maxAttempts ?? 300;
            $key = 'cookie:' . $this->tokenFromCookie($cookieValue);
        } else {
            // No cookie or tampered cookie — lowest limit
            $maxAttempts = $maxAttempts ?? 100;
            // Mint a new signed cookie so their next request gets the higher limit
            $token = Str::random(40);
            $cookieToSet = $this->signToken($token);
            $key = 'cookie:' . $token;
        }

        return [$key, $maxAttempts, null, $cookieToSet];
    }

    /**
     * Signs a random token as: token|hmac
     */
    private function signToken(string $token): string
    {
        $secret = config('app.key');
        $hmac = hash_hmac('sha256', $token, $secret);
        return $token . '|' . $hmac;
    }

    /**
     * Verifies the cookie signature
     */
    private function isValidCookie(string $cookieValue): bool
    {
        $parts = explode('|', $cookieValue, 2);
        if (count($parts) !== 2) return false;

        [$token, $hmac] = $parts;
        $secret = config('app.rate_limit_cookie_secret', config('app.key'));
        $expected = hash_hmac('sha256', $token, $secret);

        return hash_equals($expected, $hmac);
    }

    /**
     * Extracts the raw token from a signed cookie value
     */
    private function tokenFromCookie(string $cookieValue): string
    {
        return explode('|', $cookieValue, 2)[0];
    }
}
