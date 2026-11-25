<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_report_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('report_key', 64)->unique();
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('settings');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_report_defaults');
    }
};
