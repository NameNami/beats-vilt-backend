<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            ['code' => 'IPD39806', 'name' => 'Final Year Project', 'faculty' => 'Information Technology'],
            ['code' => 'ITD31403', 'name' => 'Software Engineering', 'faculty' => 'Information Technology'],
            ['code' => 'ITD34103', 'name' => 'IoT Data Analytic', 'faculty' => 'Information Technology'],
            ['code' => 'ITD10403', 'name' => 'Statistics and Data Analytic', 'faculty' => 'Information Technology'],
            ['code' => 'ITD10604', 'name' => 'Data Structure', 'faculty' => 'Information Technology'],
            ['code' => 'ITD20774', 'name' => 'IT Project Management', 'faculty' => 'Information Technology'],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
