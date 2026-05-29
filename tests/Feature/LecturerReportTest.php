<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Programme;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('lecturer can view report page', function () {
    $lecturer = User::where('role', 'lecturer')->first();
    
    $response = $this->actingAs($lecturer)->get(route('lecturer.reports'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('LecturerReport')
        ->has('attendanceTrend')
        ->has('pendingLeaves')
    );
});
