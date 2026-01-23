<?php

namespace StuMason\Kick;

use Illuminate\Support\ServiceProvider;
use StuMason\Kick\Http\Middleware\Authenticate;
use StuMason\Kick\Services\LogReader;

class KickServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kick.php', 'kick');

        $this->app->singleton(Kick::class, fn () => new Kick);

        $this->app->singleton(LogReader::class, fn ($app) => new LogReader(
            config('kick.logs.path', storage_path('logs')),
            config('kick.logs.allowed_extensions', ['log']),
            config('kick.logs.max_lines', 500)
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kick.php' => config_path('kick.php'),
            ], 'kick-config');
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (! Kick::enabled()) {
            return;
        }

        $this->app['router']->aliasMiddleware('kick.auth', Authenticate::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
