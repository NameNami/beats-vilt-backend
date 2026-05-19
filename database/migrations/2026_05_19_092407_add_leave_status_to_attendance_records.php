<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL doesn't support modifying enum easily with Blueprint in older versions, 
        // but since we are using a modern stack, we'll try raw SQL to be safe across environments.
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('early', 'on-time', 'late', 'absent', 'present', 'leave') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('early', 'on-time', 'late', 'absent', 'present') NOT NULL");
    }
};
