<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Zidbih\LiveApi\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Console');

beforeEach(function () {
    config([
        'app.env' => 'testing',
        'liveapi.enabled' => true,
        'liveapi.frozen' => false,
        'liveapi.storage_path' => storage_path('framework/testing/liveapi'),
    ]);

    File::deleteDirectory(config('liveapi.storage_path'));
});
