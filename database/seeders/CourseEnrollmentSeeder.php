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

            // Enroll all lecturers who are assigned to any lab in this course
            $lecturerIds = $labs->pluck('lecturer_id')->unique();
            foreach ($lecturerIds as $lecturerId) {
                CourseEnrollment::updateOrCreate(
                    ['user_id' => $lecturerId, 'course_id' => $course->id],
                    ['lab_id' => null, 'role' => 'lecturer']
                );
            }

            // distribute students among all labs
            $labCount = $labs->count();
            $students->each(function ($student, $index) use ($course, $labs, $labCount) {
                $lab = $labs[$index % $labCount];
                CourseEnrollment::updateOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id],
                    ['lab_id' => $lab->id, 'role' => 'student']
                );
            });
        }
    }
}
