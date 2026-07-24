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
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('spk_id')->nullable()->constrained('spk')->nullOnDelete();
            $table->string('aksi');
            $table->string('tabel_terkait')->nullable();
            $table->unsignedBigInteger('record_id_terkait')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('spk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
