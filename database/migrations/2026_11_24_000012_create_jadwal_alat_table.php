<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 28 Jadwal Alat. hari: 1 = Senin … 7 = Minggu (Carbon dayOfWeekIso). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('alat_listrik_id')->constrained('alat_listrik')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();

            $table->index(['user_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_alat');
    }
};
