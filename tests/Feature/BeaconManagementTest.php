<?php

use App\Models\Beacon;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\{post, seed};

uses(RefreshDatabase::class);

beforeEach(function () {
    //
});

test('heartbeat creates unassigned beacon if it does not exist', function () {
    $mac = 'AA:BB:CC:DD:EE:FF';
    
    post('/api/beacon/heartbeat', ['mac_address' => $mac])
        ->assertStatus(200)
        ->assertJson(['uuid' => 'pending_uuid']);

    $this->assertDatabaseHas('beacons', [
        'mac_address' => $mac,
        'status' => 'unassigned',
    ]);
});

test('heartbeat updates last_seen for existing beacon', function () {
    $beacon = Beacon::factory()->create([
        'mac_address' => '11:22:33:44:55:66',
        'status' => 'active',
        'last_seen' => now()->subHours(1),
    ]);

    post('/api/beacon/heartbeat', ['mac_address' => $beacon->mac_address])
        ->assertStatus(200)
        ->assertJson(['uuid' => $beacon->uuid]);

    $this->assertDatabaseHas('beacons', [
        'id' => $beacon->id,
        'mac_address' => $beacon->mac_address,
    ]);
    
    $beacon->refresh();
    expect($beacon->last_seen->gt(now()->subMinute()))->toBeTrue();
});

test('change-beacon-status command deactivates offline beacons and notifies admin', function () {
    // Ensure there is at least one admin to receive notification
    $admin = User::factory()->create(['role' => 'admin']);
    
    $beacon = Beacon::factory()->create([
        'status' => 'active',
        'last_seen' => now()->subMinutes(10),
    ]);

    Artisan::call('app:change-beacon-status');

    expect($beacon->refresh()->status)->toBe('inactive');
    
    $this->assertDatabaseHas('notifications', [
        'user_id' => $admin->id,
        'title' => 'Beacon Offline',
    ]);
});

test('change-beacon-status command reactivates online beacons', function () {
    $beacon = Beacon::factory()->create([
        'status' => 'inactive',
        'last_seen' => now()->subMinute(),
    ]);

    Artisan::call('app:change-beacon-status');

    expect($beacon->refresh()->status)->toBe('active');
});

test('renew-beacon-uuid command rotates UUIDs for active beacons', function () {
    $oldUuid = 'old-uuid';
    $beacon = Beacon::factory()->create([
        'status' => 'active',
        'uuid' => $oldUuid,
    ]);

    Artisan::call('app:renew-beacon-uuid');

    $beacon->refresh();
    expect($beacon->uuid)->not->toBe($oldUuid);
});
