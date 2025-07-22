<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquipoAccesorioTable extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_accesorio', function (Blueprint $table) {
            $table->id();

            // Equipo principal (ej. Proyector)
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');

            // Insumo o accesorio asociado (ej. Cable HDMI)
            $table->foreignId('insumo_id')->constrained('equipos')->onDelete('cascade');

            $table->timestamps();

            // Evita duplicados
            $table->unique(['equipo_id', 'insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_accesorio');
    }
}
