<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add confirmed_by if it doesn't exist (cancellation_reason already exists)
        if (!Schema::hasColumn('appointments', 'confirmed_by')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'confirmed_by')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropForeign(['confirmed_by']);
                $table->dropColumn('confirmed_by');
            });
        }
    }
};