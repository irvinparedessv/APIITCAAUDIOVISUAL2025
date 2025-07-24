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
        Schema::create('reserva_equipo_aula', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_equipo_id');
            $table->unsignedBigInteger('reserva_aula_id');
            $table->timestamps();

            $table->foreign('reserva_equipo_id')->references('id')->on('reserva_equipos')->onDelete('cascade');
            $table->foreign('reserva_aula_id')->references('id')->on('reserva_aulas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_equipo_aula');
    }
};
