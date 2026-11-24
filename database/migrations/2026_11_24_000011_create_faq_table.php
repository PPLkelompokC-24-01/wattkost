<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 30 FAQ & Bantuan (dikelola admin). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan', 255);
            $table->text('jawaban');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq');
    }
};
