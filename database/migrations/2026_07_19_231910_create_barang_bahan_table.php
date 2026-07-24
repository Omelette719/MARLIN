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
        Schema::create('barang_bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_pengerjaan_id')->constrained('laporan_pengerjaan')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedInteger('jumlah');
            $table->string('satuan');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_bahan');
    }
};
