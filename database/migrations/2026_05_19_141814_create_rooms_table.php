<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('service_categories')
                  ->nullOnDelete();
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('room_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('rooms')
                  ->nullOnDelete();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('requires_room')->default(true)->after('is_active');
            $table->foreignId('room_category_id')
                  ->nullable()
                  ->after('requires_room')
                  ->constrained('service_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['room_category_id']);
            $table->dropColumn(['requires_room', 'room_category_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
        });

        Schema::dropIfExists('rooms');
    }
};