<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('bir_invoice_number', 50)
                ->nullable()
                ->unique('uq_appointments_bir_invoice_number')
                ->after('privacy_policy_version');
            $table->foreignId('bir_invoice_recorded_by')
                ->nullable()
                ->after('bir_invoice_number')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('bir_invoice_recorded_at')
                ->nullable()
                ->after('bir_invoice_recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['bir_invoice_recorded_by']);
            $table->dropUnique('uq_appointments_bir_invoice_number');
            $table->dropColumn([
                'bir_invoice_number',
                'bir_invoice_recorded_by',
                'bir_invoice_recorded_at',
            ]);
        });
    }
};
