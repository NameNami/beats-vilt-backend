<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs, get};

uses(RefreshDatabase::class);

test('admin can access admin dashboard', function () {
    $admin = User::factory()->create([
        'username' => 'admin_user',
        'role' => 'admin',
    ]);

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertStatus(200);
});

test('non-admin cannot access admin dashboard', function () {
    $student = User::factory()->create([
        'username' => 'student_user',
        'role' => 'student',
    ]);

    actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertStatus(403);
});
