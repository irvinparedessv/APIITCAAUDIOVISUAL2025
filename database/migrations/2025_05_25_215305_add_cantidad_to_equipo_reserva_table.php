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
        Schema::table('equipo_reserva', function (Blueprint $table) {
            $table->integer('cantidad')->default(1)->after('equipo_id');
        });
    }

    public function down()
    {
        Schema::table('equipo_reserva', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};
