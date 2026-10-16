<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemakaian_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('alat_listrik_id')->constrained('alat_listrik')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('jam_pakai', 4, 2);
            $table->timestamps();

            $table->unique(['alat_listrik_id', 'tanggal']);
            $table->index(['user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemakaian_harian');
    }
};
