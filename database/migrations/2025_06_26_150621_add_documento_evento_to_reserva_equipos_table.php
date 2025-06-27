<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reserva_equipos', function (Blueprint $table) {
            $table->string('documento_evento')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('reserva_equipos', function (Blueprint $table) {
            $table->dropColumn('documento_evento');
        });
    }
};
