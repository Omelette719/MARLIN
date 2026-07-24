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

            $table->index(['by_user_id', 'by_spk_id']);
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
