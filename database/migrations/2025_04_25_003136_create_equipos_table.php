<?php

// database/migrations/2025_04_25_003136_create_equipos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos')->onDelete('cascade');
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('restrict');
            $table->foreignId('estado_id')->constrained('estados')->onDelete('restrict');
            $table->foreignId('tipo_reserva_id')->nullable()->constrained('tipo_reservas')->onDelete('set null');

            // Datos opcionales, depende si es equipo o insumo
            $table->string('numero_serie')->nullable()->unique(); // Si es equipo
            $table->integer('vida_util')->nullable(); // Si es equipo


            $table->text('detalles')->nullable();
            $table->date('fecha_adquisicion')->nullable();

            $table->string('imagen_normal')->nullable(); // puede sobrescribir la del modelo
            $table->string('imagen_glb')->nullable();

            $table->string('comentario')->nullable();

            // Para combos
            $table->boolean('es_componente')->default(false);
            $table->integer('reposo')->nullable();


            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
