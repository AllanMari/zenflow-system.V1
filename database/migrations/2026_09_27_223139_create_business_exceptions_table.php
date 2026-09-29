<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_exceptions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('title');
            $table->string('type'); // holiday, event, custom_hours
            $table->boolean('is_closed')->default(true);
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->text('notice_message')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_exceptions');
    }
};
