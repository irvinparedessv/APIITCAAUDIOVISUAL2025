<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modelo_accesorio', function (Blueprint $table) {
            $table->id();

            // Hace referencia al modelo de equipo
            $table->foreignId('modelo_equipo_id')
                  ->constrained('modelos')
                  ->onDelete('cascade');

            // Hace referencia al modelo de insumo
            $table->foreignId('modelo_insumo_id')
                  ->constrained('modelos')
                  ->onDelete('restrict');

            // Evita duplicados
            $table->unique(['modelo_equipo_id', 'modelo_insumo_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_accesorio');
    }
};
