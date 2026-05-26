<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveApplicationFactory extends Factory
{
    protected $model = LeaveApplication::class;

    public function definition(): array
    {
        $status = \fake()->randomElement(['pending', 'approved', 'rejected']);
        $reviewedAt = $status !== 'pending' ? \fake()->dateTimeBetween('-1 month', 'now') : null;
        
        return [
            'user_id' => User::factory(),
            'session_id' => ClassSession::factory(),
            'reviewed_by' => $status !== 'pending' ? User::factory() : null,
            'type' => \fake()->randomElement(['Medical', 'Personal', 'Emergency', 'Official']),
            'reason' => \fake()->sentence(),
            'document_path' => \fake()->boolean(70) ? 'leaves/sample_document.pdf' : null,
            'status' => $status,
            'reviewed_at' => $reviewedAt,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);
    }
}
