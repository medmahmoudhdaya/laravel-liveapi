<?php

declare(strict_types=1);
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('does not capture in production', function () {
    // Arrange
    $this->app->detectEnvironment(fn () => 'production');

    Route::middleware('api')->get('/api/prod', fn () => response()->json(['x' => 1])
    );

    $snapshotsPath = config('liveapi.storage_path').'/snapshots';

    $before = File::exists($snapshotsPath)
        ? count(File::allFiles($snapshotsPath))
        : 0;

    // Act
    $this->getJson('/api/prod');

    // Assert
    $after = File::exists($snapshotsPath)
        ? count(File::allFiles($snapshotsPath))
        : 0;

    expect($after)->toBe($before);
});
