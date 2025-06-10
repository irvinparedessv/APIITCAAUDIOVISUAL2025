<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->softDeletes(); // Agrega la columna deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('is_archived');
            $table->dropSoftDeletes(); // Elimina la columna si se revierte la migración
        });
    }
};
