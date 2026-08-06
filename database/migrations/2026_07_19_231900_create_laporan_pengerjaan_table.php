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
        Schema::create('laporan_pengerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rambu_pasang_id')->constrained('rambu_pasang')->cascadeOnDelete();
            $table->foreignId('dilaporkan_oleh')->constrained('users')->restrictOnDelete();
            $table->string('foto_sesudah')->nullable();
            $table->string('koordinat_gps')->nullable();
            $table->string('catatan_lapangan')->nullable();
            $table->string('status')->default('diajukan');
            $table->string('catatan_penolakan')->nullable();
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('divalidasi_pada')->nullable();
            $table->timestamps();

            $table->index('rambu_pasang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_pengerjaan');
    }
};
