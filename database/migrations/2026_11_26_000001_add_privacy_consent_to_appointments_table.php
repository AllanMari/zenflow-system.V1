<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('privacy_consented_at')->nullable()->after('confirmed_at');
            $table->string('privacy_policy_version', 32)->nullable()->after('privacy_consented_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_consented_at',
                'privacy_policy_version',
            ]);
        });
    }
};
