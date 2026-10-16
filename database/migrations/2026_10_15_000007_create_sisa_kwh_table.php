<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sisa_kwh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('sisa_kwh', 8, 2);
            $table->dateTime('dicatat_pada')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'dicatat_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sisa_kwh');
    }
};
