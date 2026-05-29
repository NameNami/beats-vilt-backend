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
        Schema::table('beacons', function (Blueprint $table) {
            $table->string('name')->after('id')->nullable();
            $table->integer('battery')->after('status')->nullable();
            $table->string('status')->default('Unassigned')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beacons', function (Blueprint $table) {
            $table->dropColumn(['name', 'battery']);
            $table->enum('status', ['active', 'inactive', 'maintenance', 'unassigned'])->default('unassigned')->change();
        });
    }
};
