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
        Schema::rename('rt_perwakilan', 'contact_person');

        Schema::table('contact_person', function (Blueprint $table) {
            $table->renameColumn('rtperwakilan_spk_id', 'contact_person_spk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_person', function (Blueprint $table) {
            $table->renameColumn('contact_person_spk_id', 'rtperwakilan_spk_id');
        });

        Schema::rename('contact_person', 'rt_perwakilan');
    }
};
