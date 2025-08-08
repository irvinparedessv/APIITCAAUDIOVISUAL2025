<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('estado_equipo_inicial')->nullable()->after('equipo_id');
            $table->unsignedBigInteger('estado_equipo_final')->nullable()->after('estado_equipo_inicial');

            // Si quieres referencias
            $table->foreign('estado_equipo_inicial')->references('id')->on('estados')->nullOnDelete();
            $table->foreign('estado_equipo_final')->references('id')->on('estados')->nullOnDelete();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['estado_equipo_inicial']);
            $table->dropForeign(['estado_equipo_final']);
            $table->dropColumn(['estado_equipo_inicial', 'estado_equipo_final']);
        });
    }
};
