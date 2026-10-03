<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->foreignId('attendance_id')->nullable()->change();
            $table->foreignId('schedule_exception_id')->nullable()->constrained('schedule_exceptions')->onDelete('set null');
            $table->string('exception_type', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['schedule_exception_id']);
            $table->dropColumn(['schedule_exception_id', 'exception_type']);
            $table->foreignId('attendance_id')->nullable(false)->change();
        });
    }
};
