<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Lab;
use App\Models\User;
use App\Models\CourseEnrollment;

class CourseEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $courses = Course::all();

        foreach ($courses as $course) {
            $labs = Lab::where('course_id', $course->id)->get();
            if ($labs->isEmpty()) {
                continue;
            }
            $labA = $labs->where('name', 'Lab A')->first() ?? $labs->first();
            $labB = $labs->where('name', 'Lab B')->first() ?? $labs->last();

            // enroll lecturer
            $lecturerId = optional($labs->first())->lecturer_id;
            if ($lecturerId) {
                CourseEnrollment::updateOrCreate(
                    ['user_id' => $lecturerId, 'course_id' => $course->id],
                    ['lab_id' => null, 'role' => 'lecturer']
                );
            }

            // split students 50/50 between Lab A and Lab B
            $students->each(function ($student, $index) use ($course, $labA, $labB) {
                CourseEnrollment::updateOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id],
                    ['lab_id' => $index % 2 === 0 ? $labA->id : $labB->id, 'role' => 'student']
                );
            });
        }
    }
}
