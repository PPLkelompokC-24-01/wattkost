<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_token', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_beli');
            $table->unsignedBigInteger('nominal');
            $table->decimal('jumlah_kwh', 8, 2);
            $table->string('nomor_meter', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal_beli']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_token');
    }
};
