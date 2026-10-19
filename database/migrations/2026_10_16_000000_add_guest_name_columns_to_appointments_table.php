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
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'guest_first_name')) {
                $table->string('guest_first_name')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'guest_phone')) {
                $table->string('guest_phone')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: This rollback intentionally leaves 'guest_first_name' and 'guest_phone'
     * in place to prevent data loss in pre-existing production or historical environments
     * where these columns pre-dated the tracking of this migration.
     */
    public function down(): void
    {
        // Intentionally non-destructive to safeguard existing data across all environments.
    }
};
