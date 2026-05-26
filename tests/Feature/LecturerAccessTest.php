<?php

use App\Models\User;
use App\Http\Middleware\CheckRoleWeb;

test('lecturer can access dashboard', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);

    $response = $this->actingAs($lecturer)->get(route('lecturer.dashboard'));

    $response->assertStatus(200);
});

test('student cannot access lecturer dashboard', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($student)->get(route('lecturer.dashboard'));

    $response->assertStatus(403);
});

test('guest cannot access lecturer dashboard', function () {
    $response = $this->get(route('lecturer.dashboard'));

    $response->assertRedirect(route('login'));
});
