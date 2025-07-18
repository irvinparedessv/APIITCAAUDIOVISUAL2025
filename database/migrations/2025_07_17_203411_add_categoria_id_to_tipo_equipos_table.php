<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_equipos', function (Blueprint $table) {
            $table->foreignId('categoria_id')->after('id')->constrained('categorias')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('tipo_equipos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};
