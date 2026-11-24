<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 26 Golongan Tarif. profil_kost.golongan_tarif tetap teks, dicocokkan lewat `nama`. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golongan_tarif', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 40)->unique();
            $table->unsignedInteger('daya_va');
            $table->decimal('tarif_per_kwh', 10, 2);
            $table->boolean('subsidi')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golongan_tarif');
    }
};
