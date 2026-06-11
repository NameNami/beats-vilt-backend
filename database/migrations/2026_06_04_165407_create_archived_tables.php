<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archived_class_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('semester_tag');
            $table->unsignedBigInteger('original_id');
            $table->foreignId('course_id')->constrained();
            $table->foreignId('lecturer_id')->constrained('users');
            $table->foreignId('lab_id')->nullable()->constrained();
            $table->foreignId('room_id')->constrained();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('type');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();
        });

        Schema::create('archived_course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('semester_tag');
            $table->unsignedBigInteger('original_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('archived_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('semester_tag');
            $table->unsignedBigInteger('original_id');
            // Do not use foreign keys for session_id because the original session is deleted
            $table->unsignedBigInteger('session_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('scanned_at')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_attendance_records');
        Schema::dropIfExists('archived_course_enrollments');
        Schema::dropIfExists('archived_class_sessions');
    }
};
