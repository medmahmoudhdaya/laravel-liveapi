<?php

declare(strict_types=1);

it('shows liveapi status', function () {
    $this->artisan('liveapi:status')
        ->expectsOutputToContain('LiveApi Status')
        ->assertExitCode(0);
});
