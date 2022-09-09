<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;
use App\Lti13Cache;
use App\Lti13Cookie;
use App\Lti13Database;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Packback\Lti1p3\Interfaces\ICache;
use Packback\Lti1p3\Interfaces\ICookie;
use Packback\Lti1p3\Interfaces\IDatabase;
use Packback\Lti1p3\Interfaces\ILtiServiceConnector;
use Packback\Lti1p3\LtiServiceConnector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningUnitTests()) {
            Schema::defaultStringLength(191);
        }

		if (substr( env('APP_URL'), 0, 8 ) === "https://") {
			\Illuminate\Support\Facades\URL::forceScheme('https');
		}

        $this->bootLibretextsSocialite();
       /* \DB::listen(function ($query) {
            \Log::debug($query->sql);
            //\Log::debug($query->bindings);
            //\Log::debug($query->time);
        });*/
        JWT::$leeway = 5;

    }
    private function bootLibretextsSocialite()
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'libretexts',
            function ($app) use ($socialite) {
                $config = $app['config']['services.libretexts'];
                return $socialite->buildProvider(LibretextsProvider::class, $config);
            }
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing') && class_exists(DuskServiceProvider::class)) {
            $this->app->register(DuskServiceProvider::class);
        }
        $this->app->bind(ICache::class, Lti13Cache::class);
        $this->app->bind(ICookie::class, Lti13Cookie::class);
        $this->app->bind(IDatabase::class, Lti13Database::class);
        // As of version 3.0
        $this->app->bind(ILtiServiceConnector::class, function () {
            return new LtiServiceConnector(app(ICache::class), new Client([
                'timeout' => 30,
            ]));
        });
    }
}
