<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('generates openapi.json from snapshots', function () {
    Route::middleware('api')->get('/api/docs', fn () => response()->json(['id' => 1])
    );

    $this->getJson('/api/docs');

    $this->artisan('liveapi:generate')->assertExitCode(0);

    $path = config('liveapi.storage_path').'/openapi.json';

    expect(File::exists($path))->toBeTrue();

    $json = json_decode(File::get($path), true);

    expect($json)
        ->toHaveKey('openapi')
        ->toHaveKey('paths');
});
