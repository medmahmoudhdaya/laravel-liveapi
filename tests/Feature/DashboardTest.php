<?php

declare(strict_types=1);

it('serves the openapi json endpoint', function () {
    $this->artisan('liveapi:generate');

    $this->get(route('liveapi.json'))
        ->assertOk()
        ->assertJsonStructure(['openapi', 'paths']);
});
