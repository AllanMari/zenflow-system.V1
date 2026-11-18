<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop old foreign key constraint pointing to service_categories
            $table->dropForeign(['room_category_id']);

            // Re-point foreign key constraint to room_categories
            $table->foreign('room_category_id')
                  ->references('id')
                  ->on('room_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop foreign key referencing room_categories
            $table->dropForeign(['room_category_id']);

            // Restore original foreign key referencing service_categories
            $table->foreign('room_category_id')
                  ->references('id')
                  ->on('service_categories')
                  ->nullOnDelete();
        });
    }
};
