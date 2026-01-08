<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('captures api json responses', function () {
    Route::middleware('api')->get('/api/test', fn () => response()->json(['foo' => 'bar'])
    );

    $this->getJson('/api/test')->assertOk();

    $snapshots = File::allFiles(config('liveapi.storage_path').'/snapshots');

    expect($snapshots)->not->toBeEmpty();
});
