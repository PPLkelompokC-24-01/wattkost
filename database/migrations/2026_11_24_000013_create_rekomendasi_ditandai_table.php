<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 12 Rekomendasi Hemat: rekomendasi (tips × alat) yang ditandai sudah dilakukan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_ditandai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tips_hemat_id')->constrained('tips_hemat')->cascadeOnDelete();
            $table->foreignId('alat_listrik_id')->constrained('alat_listrik')->cascadeOnDelete();
            $table->timestamps();

            // Nama eksplisit: nama otomatis Laravel 67 karakter, melebihi batas MySQL (64).
            $table->unique(['user_id', 'tips_hemat_id', 'alat_listrik_id'], 'rekomendasi_ditandai_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_ditandai');
    }
};
