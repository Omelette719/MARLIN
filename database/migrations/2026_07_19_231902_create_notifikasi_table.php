<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('judul');
            $table->string('pesan');
            $table->string('url')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('dibaca')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // The header's unread-count badge queries this on every
            // authenticated page load (WHERE user_id = ? AND dibaca = 0).
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
