<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SQL setara untuk phpMyAdmin: deploy/2026_11_25_premium_request.sql
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('premium_requested_at')->nullable()->after('premium_until');
            $table->string('premium_request_note', 255)->nullable()->after('premium_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['premium_requested_at', 'premium_request_note']);
        });
    }
};
