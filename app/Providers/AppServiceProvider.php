<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;

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
        /* \DB::listen(function ($query) {
            \Log::debug($query->sql);
            \Log::debug($query->bindings);
            \Log::debug($query->time);
        });*/


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
    }
}
