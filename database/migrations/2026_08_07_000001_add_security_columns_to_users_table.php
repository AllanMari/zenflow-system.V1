<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('remember_token');
            $table->unsignedInteger('session_version')->default(0)->after('password_changed_at');
            $table->timestamp('terms_accepted_at')->nullable()->after('session_version');
            $table->timestamp('privacy_consented_at')->nullable()->after('terms_accepted_at');
            $table->timestamp('email_verified_at')->nullable()->after('privacy_consented_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_changed_at',
                'session_version',
                'terms_accepted_at',
                'privacy_consented_at',
                'email_verified_at',
            ]);
        });
    }
};