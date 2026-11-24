<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 27 Anggota Patungan: teman patungan tanpa akun (nama + kamar). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_patungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->string('kamar', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_patungan');
    }
};
