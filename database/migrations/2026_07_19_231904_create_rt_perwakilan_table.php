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
        Schema::create('rt_perwakilan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('no_telepon')->nullable();
            $table->foreignId('rtperwakilan_spk_id')->constrained('spk')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rt_perwakilan');
    }
};
