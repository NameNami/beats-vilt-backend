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
            Lab::create([
                'course_id'   => $course->id,
                'lecturer_id' => $lecturers[$index % $lecturers->count()]->id,
                'name'        => 'Lab A',
                'capacity'    => 15,
            ]);

            Lab::create([
                'course_id'   => $course->id,
                'lecturer_id' => $lecturers[($index + 1) % $lecturers->count()]->id,
                'name'        => 'Lab B',
                'capacity'    => 15,
            ]);
        }
    }
}
