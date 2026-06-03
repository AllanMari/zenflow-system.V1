<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Change status from ENUM to VARCHAR using pure Laravel syntax
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
        
        // 2. Add the rest of your business audit fields
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('deposit_required', 10, 2)->nullable()->after('total_price');
            $table->string('no_show_reason')->nullable()->after('cancellation_reason');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->after('no_show_reason');
            $table->timestamp('refund_approved_at')->nullable()->after('refunded_by');
            $table->foreignId('rescheduled_from')->nullable()->constrained('appointments')->after('refund_approved_at');
            $table->timestamp('rescheduled_at')->nullable()->after('rescheduled_from');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_required',
                'no_show_reason',
                'refunded_by',
                'refund_approved_at',
                'rescheduled_from',
                'rescheduled_at',
            ]);
            
            $table->string('status', 20)->default('pending')->change();
        });
    }
};