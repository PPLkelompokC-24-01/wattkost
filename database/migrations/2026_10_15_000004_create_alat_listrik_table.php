<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alat_listrik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignId('katalog_alat_id')->nullable()->constrained('katalog_alat')->nullOnDelete();
            $table->string('nama_alat', 100);
            $table->string('kategori', 30)->default('Lainnya');
            $table->unsignedInteger('daya_watt');
            $table->unsignedSmallInteger('jumlah')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alat_listrik');
    }
};
