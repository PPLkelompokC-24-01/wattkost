<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PBI 09 Pengingat Token: pengaturan pengingat per pengguna. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_pengguna', function (Blueprint $table) {
            $table->boolean('pengingat_aktif')->default(true);
            $table->unsignedTinyInteger('pengingat_batas_hari')->default(3);
            $table->boolean('pengingat_email')->default(false);
            $table->date('pengingat_dicek_pada')->nullable();
            $table->dateTime('pengingat_terakhir_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_pengguna', function (Blueprint $table) {
            $table->dropColumn([
                'pengingat_aktif',
                'pengingat_batas_hari',
                'pengingat_email',
                'pengingat_dicek_pada',
                'pengingat_terakhir_pada',
            ]);
        });
    }
};
