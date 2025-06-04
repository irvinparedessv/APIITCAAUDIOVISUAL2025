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
        Schema::table('reserva_aulas', function (Blueprint $table) {
            $table->text('comentario')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('reserva_aulas', function (Blueprint $table) {
            $table->dropColumn('comentario');
        });
    }
};
