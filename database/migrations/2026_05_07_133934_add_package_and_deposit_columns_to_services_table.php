<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'included_services')) {
                $table->json('included_services')->nullable()->after('is_package');
            }
            if (!Schema::hasColumn('services', 'deposit_percentage_min')) {
                $table->integer('deposit_percentage_min')->nullable()->after('requires_prepayment');
            }
            if (!Schema::hasColumn('services', 'deposit_percentage_max')) {
                $table->integer('deposit_percentage_max')->nullable()->after('deposit_percentage_min');
            }
            if (!Schema::hasColumn('services', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('services', 'landing_description')) {
                $table->text('landing_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('services', 'image')) {
                $table->string('image')->nullable()->after('landing_description');
            }
            if (!Schema::hasColumn('services', 'show_on_landing')) {
                $table->boolean('show_on_landing')->default(true)->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('services', 'show_on_landing')) $columns[] = 'show_on_landing';
            if (Schema::hasColumn('services', 'image')) $columns[] = 'image';
            if (Schema::hasColumn('services', 'landing_description')) $columns[] = 'landing_description';
            if (Schema::hasColumn('services', 'discount_price')) $columns[] = 'discount_price';
            if (Schema::hasColumn('services', 'deposit_percentage_max')) $columns[] = 'deposit_percentage_max';
            if (Schema::hasColumn('services', 'deposit_percentage_min')) $columns[] = 'deposit_percentage_min';
            if (Schema::hasColumn('services', 'included_services')) $columns[] = 'included_services';
            
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};