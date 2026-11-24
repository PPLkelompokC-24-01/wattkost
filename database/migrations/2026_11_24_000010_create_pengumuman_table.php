<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 29 Pengumuman (dikelola admin). tampil_sampai null = tampil terus. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->text('isi');
            $table->date('tampil_mulai');
            $table->date('tampil_sampai')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
