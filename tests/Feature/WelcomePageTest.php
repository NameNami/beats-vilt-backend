<?php

use function Pest\Laravel\get;

test('welcome page is accessible', function () {
    get(route('home'))
        ->assertStatus(200);
});
