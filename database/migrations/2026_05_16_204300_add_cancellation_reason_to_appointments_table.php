<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the column DOES NOT exist before trying to add it
        if (!Schema::hasColumn('appointments', 'cancellation_reason')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('cancellation_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        // Check if the column exists before trying to drop it
        if (Schema::hasColumn('appointments', 'cancellation_reason')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('cancellation_reason');
            });
        }
    }
};