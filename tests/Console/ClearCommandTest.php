<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('clears snapshots', function () {
    Route::middleware('api')->get('/api/clear', fn () => response()->json(['x' => 1])
    );

    $this->getJson('/api/clear');

    $this->artisan('liveapi:clear')
        ->expectsConfirmation('This will delete all captured API snapshots. Continue?', 'yes')
        ->assertExitCode(0);

    expect(File::exists(config('liveapi.storage_path').'/snapshots'))->toBeFalse();
});
