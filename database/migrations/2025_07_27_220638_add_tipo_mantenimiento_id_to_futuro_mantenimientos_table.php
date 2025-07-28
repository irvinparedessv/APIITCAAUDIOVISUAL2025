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
        Schema::table('futuro_mantenimientos', function (Blueprint $table) {
            $table->foreignId('tipo_mantenimiento_id')
                ->after('equipo_id') // opcional, para ubicar la columna después de equipo_id
                ->constrained('tipo_mantenimientos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('futuro_mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['tipo_mantenimiento_id']);
            $table->dropColumn('tipo_mantenimiento_id');
        });
    }
};
