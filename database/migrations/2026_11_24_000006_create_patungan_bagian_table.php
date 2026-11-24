<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 19 Patungan Token: bagian tiap orang. anggota_patungan_id null = bagian pembeli sendiri ("Saya"). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patungan_bagian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patungan_token_id')->constrained('patungan_token')->cascadeOnDelete();
            $table->foreignId('anggota_patungan_id')->nullable()->constrained('anggota_patungan')->nullOnDelete();
            $table->unsignedBigInteger('nominal');
            $table->dateTime('lunas_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patungan_bagian');
    }
};
