<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Zidbih\LiveApi\LiveApiServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LiveApiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('liveapi.enabled', true);
        $app['config']->set('liveapi.frozen', false);
        $app['config']->set(
            'liveapi.storage_path',
            storage_path('framework/testing/liveapi')
        );
    }
}
