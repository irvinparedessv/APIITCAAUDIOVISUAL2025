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
        Schema::table('reserva_equipos', function (Blueprint $table) {
            $table->foreignId('tipo_reserva_id')->nullable()->constrained('tipo_reservas');
        });
    }

    public function down(): void
    {
        Schema::table('reserva_equipos', function (Blueprint $table) {
            $table->dropForeign(['tipo_reserva_id']);
            $table->dropColumn('tipo_reserva_id');
        });
    }

};
