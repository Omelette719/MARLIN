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
        Schema::create('spk', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('wilayah');
            $table->string('jalan')->nullable();
            $table->string('rt')->nullable();
            $table->string('kelurahan')->nullable();
            $table->date('deadline');
            $table->boolean('prioritas')->default(false);
            $table->string('urgensi');
            $table->string('status')->default('aktif');
            $table->string('jenis_spk')->default('pasang_baru');
            $table->string('asal_permintaan');
            $table->string('keterangan_asal')->nullable();
            $table->string('perihal')->nullable();
            $table->date('tanggal_survei')->nullable();
            $table->string('file_referensi')->nullable();
            $table->string('catatan_pekerja_tambahan')->nullable();
            $table->timestamp('laporan_akhir_diajukan_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spk');
    }
};
