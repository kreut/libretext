<?php

namespace App\Providers;

use App\Providers\JWT\SwappableSecretLcobucci;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Providers\JWT\Lcobucci;

/**
 * Swaps tymon/jwt-auth's JWT provider for one whose setSecret() takes effect.
 * See App\Providers\JWT\SwappableSecretLcobucci for why.
 */
class JwtSecretServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('tymon.jwt.provider.jwt', function ($provider) {
            if ($provider instanceof SwappableSecretLcobucci || !$provider instanceof Lcobucci) {
                return $provider;
            }

            return new SwappableSecretLcobucci(
                $provider->getSecret(),
                $provider->getAlgo(),
                $provider->getKeys()
            );
        });
    }
}
