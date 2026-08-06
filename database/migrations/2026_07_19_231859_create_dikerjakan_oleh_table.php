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
        Schema::create('dikerjakan_oleh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('by_spk_id')->constrained('spk')->cascadeOnDelete();
            $table->foreignId('by_user_id')->constrained('users')->restrictOnDelete();
            $table->boolean('is_perwakilan')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Unique (not just an index): tambahAnggota()/daftarkanTim() only
            // check for an existing row in PHP before inserting, which is a
            // real TOCTOU race between two concurrent requests — this is the
            // actual backstop. by_spk_id leads because "who's on this SPK's
            // team" (by_spk_id alone) is the far more common lookup than the
            // reverse; by_user_id gets its own index for "which SPKs has
            // this petugas joined" (Dashboard/Riwayat/SpkDikerjakan).
            $table->unique(['by_spk_id', 'by_user_id']);
            $table->index('by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dikerjakan_oleh');
    }
};
