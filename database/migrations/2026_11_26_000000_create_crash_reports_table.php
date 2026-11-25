<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SQL setara untuk phpMyAdmin: deploy/2026_11_26_crash_reports.sql
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crash_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('app_version', 20)->nullable();
            $table->string('device', 100)->nullable();
            $table->string('android', 20)->nullable();
            $table->string('message', 500);
            $table->mediumText('stack');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crash_reports');
    }
};
