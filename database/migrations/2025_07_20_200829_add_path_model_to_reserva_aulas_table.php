<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reserva_aulas', function (Blueprint $table) {
            $table->string('path_model')->nullable()->after('comentario');
        });
    }

    public function down(): void
    {
        Schema::table('reserva_aulas', function (Blueprint $table) {
            $table->dropColumn('path_model');
        });
    }
};
