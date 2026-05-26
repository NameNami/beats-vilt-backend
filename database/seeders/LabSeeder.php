<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lab;
use App\Models\Course;
use App\Models\User;

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
                Lab::create([
                    'course_id'   => $course->id,
                    'lecturer_id' => $lecturers[($index + $i - 1) % $lecturers->count()]->id,
                    'name'        => 'L' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'capacity'    => 15,
                ]);
            }
        }
    }
}
