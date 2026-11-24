<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 19 Patungan Token: 1 pembelian token = maksimal 1 patungan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patungan_token', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pembelian_token_id')->unique()->constrained('pembelian_token')->cascadeOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patungan_token');
    }
};
