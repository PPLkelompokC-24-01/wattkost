<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 22 Deteksi Lonjakan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peringatan_lonjakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('kwh_tanggal', 8, 2);
            $table->decimal('kwh_rata_rata', 8, 2);
            $table->decimal('rasio', 5, 2);
            $table->string('tingkat', 10);
            $table->foreignId('alat_listrik_id')->nullable()->constrained('alat_listrik')->nullOnDelete();
            $table->dateTime('dicek_pada')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peringatan_lonjakan');
    }
};
