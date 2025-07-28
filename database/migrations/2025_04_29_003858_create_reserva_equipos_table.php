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
        Schema::create('equipo_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_equipo_id')->constrained('reserva_equipos')->onDelete('cascade');
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->text('comentario')->nullable();
            $table->dateTime('fecha_inicio_reposo')->nullable();
            $table->dateTime('fecha_fin_reposo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipo_reserva');
    }
};
