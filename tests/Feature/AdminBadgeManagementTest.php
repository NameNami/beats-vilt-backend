<?php

use App\Models\Badge;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin can create a badge with valid data', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $response = actingAs($this->admin)->post(route('admin.badges.store'), [
        'name' => 'Test Badge',
        'description' => 'Test Description',
        'type' => 'achievement',
        'requirement_type' => 'present_checkins',
        'requirement_value' => 5,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('badges', [
        'name' => 'Test Badge',
        'type' => 'achievement',
    ]);
});

test('admin cannot create a badge with invalid type', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $response = actingAs($this->admin)->post(route('admin.badges.store'), [
        'name' => 'Invalid Badge',
        'description' => 'Invalid Type',
        'type' => 'invalid_type',
        'requirement_type' => 'present_checkins',
        'requirement_value' => 5,
    ]);

    $response->assertSessionHasErrors(['type']);
    $this->assertDatabaseMissing('badges', [
        'name' => 'Invalid Badge',
    ]);
});

test('admin can update a badge with valid data', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $badge = Badge::create([
        'name' => 'Old Name',
        'description' => 'Old Description',
        'type' => 'xp',
        'requirement_type' => 'total_xp',
        'requirement_value' => 100,
    ]);

    $response = actingAs($this->admin)->post(route('admin.badges.update', $badge->id), [
        'name' => 'New Name',
        'description' => 'New Description',
        'type' => 'streak',
        'requirement_type' => 'streak_count',
        'requirement_value' => 10,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('badges', [
        'id' => $badge->id,
        'name' => 'New Name',
        'type' => 'streak',
    ]);
});
