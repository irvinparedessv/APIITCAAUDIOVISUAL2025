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
        Schema::create('reserva_aulas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aula_id')->constrained('aulas')->onDelete('cascade');
            $table->date('fecha');
            $table->string('horario');

            // Opcional: si usas autenticación y quieres guardar quién hizo la reserva
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Opcional: puedes incluir estado (pendiente, confirmada, cancelada)
            $table->string('estado')->default('pendiente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_aulas');
    }
};
