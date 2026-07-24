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
        Schema::create('laporan_kondisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rambu_id')->constrained('rambu')->restrictOnDelete();
            $table->foreignId('dilaporkan_oleh')->constrained('users')->restrictOnDelete();
            $table->string('kondisi_dilaporkan');
            $table->string('foto')->nullable();
            $table->string('catatan')->nullable();
            $table->string('status_tindak_lanjut')->default('baru');
            $table->foreignId('ditindaklanjuti_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_kondisi');
    }
};
