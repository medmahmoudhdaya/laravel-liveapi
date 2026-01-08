<?php

declare(strict_types=1);

namespace Zidbih\LiveApi;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Zidbih\LiveApi\Console\GenerateSpecsCommand;
use Zidbih\LiveApi\Console\LiveApiClearCommand;
use Zidbih\LiveApi\Console\LiveApiStatusCommand;
use Zidbih\LiveApi\Http\Middleware\CaptureTraffic;

final class LiveApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/liveapi.php',
            'liveapi'
        );
    }

    public function boot(): void
    {
        // Hard-disable in production (no override)
        if ($this->app->isProduction()) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'liveapi');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->offerPublishing();
        $this->registerMiddleware();
    }

    protected function offerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            GenerateSpecsCommand::class,
            LiveApiStatusCommand::class,
            LiveApiClearCommand::class,
        ]);

        $this->publishes([
            __DIR__.'/../config/liveapi.php' => config_path('liveapi.php'),
        ], 'liveapi-config');
    }

    private function registerMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);

        // Append once to the API group
        $kernel->appendMiddlewareToGroup('api', CaptureTraffic::class);
    }
}
