<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreign('service_id')
                  ->references('id')
                  ->on('services')
                  ->onDelete('restrict'); // Explicitly prevent deletion of booked services
        });
    }

    public function down(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreign('service_id')
                  ->references('id')
                  ->on('services');
        });
    }
};