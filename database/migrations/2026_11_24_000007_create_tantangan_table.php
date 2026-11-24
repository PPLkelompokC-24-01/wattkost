<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 20 Tantangan Hemat (dikelola admin). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tantangan', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->string('jenis_target', 20);
            $table->decimal('nilai_target', 8, 2);
            $table->unsignedSmallInteger('durasi_hari');
            $table->unsignedSmallInteger('poin');
            $table->boolean('aktif')->default(true);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tantangan');
    }
};
