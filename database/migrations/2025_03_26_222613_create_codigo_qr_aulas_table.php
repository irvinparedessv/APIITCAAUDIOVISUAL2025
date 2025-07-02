<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('codigo_qr_aulas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // ID tipo UUID
            $table->foreignId('reserva_id')->references('id')->on('reserva_aulas')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigo_q_r_aulas');
    }
};
