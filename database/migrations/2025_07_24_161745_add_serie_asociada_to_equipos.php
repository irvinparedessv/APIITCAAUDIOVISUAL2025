<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSerieAsociadaToEquipos extends Migration
{
    public function up()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('serie_asociada')->nullable()->after('numero_serie');
        });
    }

    public function down()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('serie_asociada');
        });
    }
}
