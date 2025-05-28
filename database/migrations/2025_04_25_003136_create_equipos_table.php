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
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true); 
            $table->integer('cantidad')->default(0);
            $table->boolean('is_deleted')->default(false); 
            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos') ->onDelete('cascade'); 
            $table->foreignId('tipo_reserva_id')->constrained('tipo_reservas')->onDelete('cascade');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
