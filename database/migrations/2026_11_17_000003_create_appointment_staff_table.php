<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['primary', 'assisting'])->default('primary');
            $table->timestamps();

            $table->unique(['appointment_id', 'user_id'], 'uq_appointment_staff_user');
        });

        // Backfill existing assigned primary therapists from appointments.user_id.
        // Uses CURRENT_TIMESTAMP for cross-database compatibility (MySQL + SQLite test env).
        DB::statement("
            INSERT INTO appointment_staff (appointment_id, user_id, role, created_at, updated_at)
            SELECT id, user_id, 'primary', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM appointments
            WHERE user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_staff');
    }
};
