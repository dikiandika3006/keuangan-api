<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('plan', 20)->default('free')->after('password');
            $table->timestamp('premium_until')->nullable()->after('plan');
            $table->unsignedBigInteger('sync_seq')->default(0)->after('premium_until');
        });

        // Penyimpanan sinkronisasi offline-first: satu baris per entitas milik pengguna.
        Schema::create('sync_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entity', 30);                    // account, category, txn, recurring, debt, bill_occ
            $table->string('record_id', 64);                 // UUID dari aplikasi
            $table->json('data');
            $table->unsignedBigInteger('client_updated_at'); // epoch ms dari perangkat (last-write-wins)
            $table->unsignedBigInteger('deleted_at')->nullable();
            $table->unsignedBigInteger('seq');               // urutan perubahan per pengguna, untuk pull
            $table->timestamps();
            $table->unique(['user_id', 'entity', 'record_id']);
            $table->index(['user_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_records');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'plan', 'premium_until', 'sync_seq']);
        });
    }
};
