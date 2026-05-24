<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $lecturers = User::where('role', 'lecturer')->get();
        $sessions = ClassSession::all();

        if ($students->isEmpty() || $sessions->isEmpty()) {
            return;
        }

        // Create some pending leave applications
        foreach ($students->random(5) as $student) {
            $session = $sessions->random();
            
            // Check if application already exists (unique constraint)
            if (LeaveApplication::where('user_id', $student->id)->where('session_id', $session->id)->exists()) {
                continue;
            }

            LeaveApplication::create([
                'user_id' => $student->id,
                'session_id' => $session->id,
                'type' => 'Medical',
                'reason' => 'I have a fever and need to visit the clinic.',
                'document_path' => 'leaves/mc_sample.pdf',
                'status' => 'pending',
            ]);
        }

        // Create some approved leave applications
        foreach ($students->random(3) as $student) {
            $session = $sessions->random();
            $lecturer = $lecturers->random();

            if (LeaveApplication::where('user_id', $student->id)->where('session_id', $session->id)->exists()) {
                continue;
            }

            LeaveApplication::create([
                'user_id' => $student->id,
                'session_id' => $session->id,
                'reviewed_by' => $lecturer->id,
                'type' => 'Personal',
                'reason' => 'Family emergency back home.',
                'document_path' => null,
                'status' => 'approved',
                'reviewed_at' => now()->subDays(rand(1, 5)),
            ]);
        }

        // Create some rejected leave applications
        foreach ($students->random(2) as $student) {
            $session = $sessions->random();
            $lecturer = $lecturers->random();

            if (LeaveApplication::where('user_id', $student->id)->where('session_id', $session->id)->exists()) {
                continue;
            }

            LeaveApplication::create([
                'user_id' => $student->id,
                'session_id' => $session->id,
                'reviewed_by' => $lecturer->id,
                'type' => 'Emergency',
                'reason' => 'Last minute request without supporting documents.',
                'document_path' => null,
                'status' => 'rejected',
                'reviewed_at' => now()->subDays(rand(1, 2)),
            ]);
        }
    }
}
