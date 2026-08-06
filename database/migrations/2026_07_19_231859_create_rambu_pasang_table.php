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
        Schema::create('rambu_pasang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rambu_spk_id')->constrained('spk')->cascadeOnDelete();
            $table->foreignId('rambu_id')->constrained('rambu')->restrictOnDelete();
            $table->foreignId('laporan_kondisi_id')->nullable()->constrained('laporan_kondisi')->restrictOnDelete();
            $table->string('jenis_pekerjaan');
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('foto_survei')->nullable();
            $table->string('catatan_instruksi')->nullable();
            $table->string('catatan_pembatalan')->nullable();
            $table->string('status')->default('belum');
            $table->timestamps();

            $table->index('status');
            $table->index('rambu_spk_id');
            $table->index('rambu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rambu_pasang');
    }
};
