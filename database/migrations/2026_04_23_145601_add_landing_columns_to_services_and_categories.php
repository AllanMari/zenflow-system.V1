<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->boolean('show_on_landing')->default(true)->after('color');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->text('landing_description')->nullable()->after('image');
            $table->boolean('show_on_landing')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('show_on_landing');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['image', 'landing_description', 'show_on_landing']);
        });
    }
};