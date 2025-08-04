<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMantenimientosTable extends Migration
{
    public function up()
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->date('fecha_mantenimiento');
            $table->date('fecha_mantenimiento_final')->nullable();
            $table->time('hora_mantenimiento_inicio')->nullable();
            $table->time('hora_mantenimiento_final')->nullable();
            $table->text('detalles')->nullable();
            $table->text('comentario')->nullable();
            $table->foreignId('tipo_id')->constrained('tipo_mantenimientos')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('futuro_mantenimiento_id')->nullable()->constrained('futuro_mantenimientos')->onDelete('set null');
            $table->integer('vida_util')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mantenimientos');
    }
};
