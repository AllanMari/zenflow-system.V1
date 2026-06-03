<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->integer('deposit_percentage_min')->nullable()->after('requires_prepayment');
            $table->integer('deposit_percentage_max')->nullable()->after('deposit_percentage_min');
        });
        
        Schema::table('service_categories', function (Blueprint $table) {
            $table->integer('deposit_percentage_min')->nullable()->after('color');
            $table->integer('deposit_percentage_max')->nullable()->after('deposit_percentage_min');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['deposit_percentage_min', 'deposit_percentage_max']);
        });
        
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn(['deposit_percentage_min', 'deposit_percentage_max']);
        });
    }
};