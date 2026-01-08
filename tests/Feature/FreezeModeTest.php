<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('does not capture traffic when frozen', function () {
    // Arrange
    config(['liveapi.frozen' => true]);

    Route::middleware('api')->get('/api/frozen', fn () => response()->json(['x' => 1])
    );

    $snapshotsPath = config('liveapi.storage_path').'/snapshots';

    $before = File::exists($snapshotsPath)
        ? count(File::allFiles($snapshotsPath))
        : 0;

    // Act
    $this->getJson('/api/frozen');

    // Assert
    $after = File::exists($snapshotsPath)
        ? count(File::allFiles($snapshotsPath))
        : 0;

    expect($after)->toBe($before);
});
