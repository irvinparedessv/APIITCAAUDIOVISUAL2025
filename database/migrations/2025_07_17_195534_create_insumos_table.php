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
    Schema::create('insumos', function (Blueprint $table) {
        $table->id();

        $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos')->onDelete('cascade');
        $table->foreignId('marca_id')->constrained('marcas')->onDelete('restrict');
        $table->foreignId('modelo_id')->constrained('modelos')->onDelete('restrict');
        $table->foreignId('estado_id')->constrained('estados')->onDelete('restrict');

        $table->foreignId('tipo_reserva_id')->constrained('tipo_reservas')->onDelete('cascade');

        $table->integer('cantidad')->default(0); // cantidad para insumos
        $table->text('detalles')->nullable();

        $table->date('fecha_adquisicion')->nullable();

        $table->boolean('is_deleted')->default(false);

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
