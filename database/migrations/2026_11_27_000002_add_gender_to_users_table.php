<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add gender classification to users.
     *
     * - Applies to therapist/staff accounts only.
     * - Nullable: existing accounts retain NULL until manually set by an admin.
     * - Only 'male' and 'female' are valid values; NULL means "not specified".
     * - Gender is classification/display information only and does not affect
     *   scheduling, availability, or automatic assignment logic.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])
                  ->nullable()
                  ->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
