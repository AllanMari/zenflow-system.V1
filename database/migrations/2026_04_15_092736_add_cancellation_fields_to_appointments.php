<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Track who confirmed the booking
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
            // Track why it was cancelled
            $table->string('cancellation_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['confirmed_by', 'cancellation_reason']);
        });
    }
};