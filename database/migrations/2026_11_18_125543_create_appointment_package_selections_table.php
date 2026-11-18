<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_package_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->unsignedBigInteger('choice_group_id')->nullable();
            $table->unsignedBigInteger('selected_service_id')->nullable();
            $table->string('group_label_snapshot');
            $table->string('selected_service_name_snapshot');
            $table->timestamps();

            $table->foreign('choice_group_id')
                  ->references('id')->on('package_choice_groups')
                  ->nullOnDelete();
            $table->foreign('selected_service_id')
                  ->references('id')->on('services')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_package_selections');
    }
};
