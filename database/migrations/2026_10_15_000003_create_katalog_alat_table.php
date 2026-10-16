<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('katalog_alat', function (Blueprint $table) {
            $table->id();
            $table->string('nama_alat', 100)->unique();
            $table->string('kategori', 30);
            $table->unsignedInteger('daya_watt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('katalog_alat');
    }
};
