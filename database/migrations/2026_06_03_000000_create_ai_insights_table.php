<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 20); // daily, weekly, monthly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metrics_input'); // what we sent to AI
            $table->json('insights_output'); // what AI returned
            $table->string('model_used', 50)->default('qwen2.5:7b');
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();

            $table->index(['period_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};