<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();
        $lecturers = User::where('role', 'lecturer')->get();

        foreach ($courses as $index => $course) {
            for ($i = 1; $i <= 4; $i++) {
                $name = 'L'.str_pad($i, 2, '0', STR_PAD_LEFT);
                Lab::updateOrCreate(
                    ['course_id' => $course->id, 'name' => $name],
                    [
                        'lecturer_id' => $lecturers[($index + $i - 1) % $lecturers->count()]->id,
                        'capacity' => 15,
                    ]
                );
            }
        }
    }
}
