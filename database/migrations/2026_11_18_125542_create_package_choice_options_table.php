<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_choice_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('choice_group_id')->constrained('package_choice_groups')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['choice_group_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_choice_options');
    }
};
