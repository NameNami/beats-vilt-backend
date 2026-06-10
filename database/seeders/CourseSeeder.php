<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

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
            Course::updateOrCreate(
                ['code' => $course['code']],
                ['name' => $course['name'], 'faculty' => $course['faculty']]
            );
        }
    }
}
