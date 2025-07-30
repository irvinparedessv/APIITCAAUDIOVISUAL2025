<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuturoMantenimientosTable extends Migration
{
    public function up()
    {
        Schema::create('futuro_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('tipo_mantenimiento_id')->constrained('tipo_mantenimientos')->onDelete('cascade');
            $table->date('fecha_mantenimiento');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->time('hora_mantenimiento_inicio')->nullable();
            $table->time('hora_mantenimiento_final')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('futuro_mantenimientos');
    }
}
