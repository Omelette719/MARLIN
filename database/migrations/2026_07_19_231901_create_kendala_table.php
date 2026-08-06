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
        Schema::create('kendala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rambu_pasang_id')->constrained('rambu_pasang')->cascadeOnDelete();
            $table->foreignId('dilaporkan_oleh')->constrained('users')->restrictOnDelete();
            $table->string('alasan');
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->index('rambu_pasang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kendala');
    }
};
