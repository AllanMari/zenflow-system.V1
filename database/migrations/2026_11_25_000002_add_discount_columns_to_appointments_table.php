<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'discount_type')) {
                $table->string('discount_type', 32)->nullable()->after('total_price');
            }
            if (!Schema::hasColumn('appointments', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->nullable()->after('discount_type');
            }
            if (!Schema::hasColumn('appointments', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
            }
            if (!Schema::hasColumn('appointments', 'original_total_price')) {
                $table->decimal('original_total_price', 10, 2)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('appointments', 'discount_applied_by')) {
                $table->foreignId('discount_applied_by')->nullable()->after('original_total_price')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('appointments', 'discount_applied_at')) {
                $table->timestamp('discount_applied_at')->nullable()->after('discount_applied_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'discount_applied_by')) {
                $table->dropForeign(['discount_applied_by']);
            }
            $columns = array_values(array_filter([
                Schema::hasColumn('appointments', 'discount_type') ? 'discount_type' : null,
                Schema::hasColumn('appointments', 'discount_percent') ? 'discount_percent' : null,
                Schema::hasColumn('appointments', 'discount_amount') ? 'discount_amount' : null,
                Schema::hasColumn('appointments', 'original_total_price') ? 'original_total_price' : null,
                Schema::hasColumn('appointments', 'discount_applied_by') ? 'discount_applied_by' : null,
                Schema::hasColumn('appointments', 'discount_applied_at') ? 'discount_applied_at' : null,
            ]));
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
