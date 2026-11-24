<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 20 Tantangan Hemat & PBI 21 Leaderboard. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_tantangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tantangan_id')->constrained('tantangan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('mulai');
            $table->date('selesai');
            $table->string('status', 10);
            $table->unsignedSmallInteger('poin_didapat')->default(0);
            $table->string('catatan', 255)->nullable();
            $table->dateTime('dinilai_pada')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_tantangan');
    }
};
