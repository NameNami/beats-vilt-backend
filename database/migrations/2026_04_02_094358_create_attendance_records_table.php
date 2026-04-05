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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->dateTime('check_in_time');
            $table->enum('status', ['early', 'on-time', 'late', 'absent']);
            $table->enum('checkin_method', ['BLE', 'dynamic_qr', 'static_qr', 'manual']);
            $table->unique(['user_id', 'session_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
