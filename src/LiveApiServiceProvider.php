<?php

declare(strict_types=1);

namespace Zidbih\LiveApi;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Zidbih\LiveApi\Http\Middleware\CaptureTraffic;

final class LiveApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/liveapi.php', 'liveapi');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            return;
        }

        if (! config('liveapi.enabled')) {
            return;
        }

        $this->offerPublishing();
        $this->registerMiddleware();
    }

    /**
     * Setup the resource publishing groups.
     */
    protected function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Zidbih\LiveApi\Console\GenerateSpecsCommand::class,
            ]);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/liveapi.php' => config_path('liveapi.php'),
            ], 'liveapi-config');
        }
    }

    /**
     * Inject the middleware into the 'api' middleware group.
     */
    private function registerMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);

        // This ensures the middleware runs on all routes within the 'api' group.
        $kernel->appendMiddlewareToGroup('api', CaptureTraffic::class);
    }
}
