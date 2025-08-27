<?php

namespace Muhammadsalman\LaravelSso;

use Illuminate\Support\ServiceProvider;
use Muhammadsalman\LaravelSso\Core\SSOManager;
use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Support\DeepLinkHandler;
use Muhammadsalman\LaravelSso\Support\RedirectUrlManager;

/**
 * Auto-discovers via composer "extra.laravel.providers".
 * Registers config + core singletons. No coupling to User/Passport.
 */
class LaravelSSOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-sso.php', 'laravel-sso');

        $this->app->singleton(PlatformService::class, fn() =>
            new PlatformService(config('laravel-sso.platforms'))
        );

        $this->app->singleton(DeepLinkHandler::class, fn($app) =>
            new DeepLinkHandler($app->make(PlatformService::class))
        );

        $this->app->singleton(RedirectUrlManager::class, fn($app) =>
            new RedirectUrlManager(
                $app->make(PlatformService::class),
                $app->make(DeepLinkHandler::class)
            )
        );

        $this->app->singleton(SSOManager::class, fn($app) =>
            new SSOManager(
                $app->make(PlatformService::class),
                config('laravel-sso.providers')
            )
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-sso.php' => config_path('laravel-sso.php'),
        ], 'laravel-sso-config');
    }
}
