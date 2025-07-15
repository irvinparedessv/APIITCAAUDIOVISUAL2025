<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reserva_aula_bloques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reserva_aulas')->onDelete('cascade');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('dia');
            $table->string('estado')->default('pendiente');
            $table->boolean('recurrente')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserva_aula_bloques');
    }
};
