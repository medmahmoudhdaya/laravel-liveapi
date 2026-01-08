<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('generates openapi.json from captured snapshots', function () {
    Route::middleware('api')->get('/api/generate-test', fn () => response()->json(['id' => 1])
    );

    $this->getJson('/api/generate-test')->assertOk();

    $this->artisan('liveapi:generate')
        ->assertExitCode(0);

    $path = config('liveapi.storage_path').'/openapi.json';

    expect(File::exists($path))->toBeTrue();

    $json = json_decode(File::get($path), true);

    expect($json)
        ->toBeArray()
        ->toHaveKey('openapi', '3.1.0')
        ->toHaveKey('paths')
        ->and($json['paths'])->not->toBeEmpty();
});

it('can generate spec while frozen', function () {
    config(['liveapi.frozen' => true]);

    $this->artisan('liveapi:generate --force')
        ->assertExitCode(0);

    expect(File::exists(
        config('liveapi.storage_path').'/openapi.json'
    ))->toBeTrue();
});
