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
        Schema::create('rambu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_rambu_id')->constrained('jenis_rambu')->restrictOnDelete();
            $table->string('wilayah');
            $table->string('jalan')->nullable();
            $table->string('rt')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('lokasi');
            $table->string('koordinat');
            $table->string('kondisi_terkini')->default('baik');
            $table->boolean('sudah_terpasang')->default(false);
            $table->timestamps();

            $table->index('wilayah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rambu');
    }
};
