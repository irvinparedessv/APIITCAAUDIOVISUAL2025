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
        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path_modelo')->nullable();
            $table->integer('capacidad_maxima');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->boolean('deleted')->default(false);
            $table->decimal('escala', 5, 2)->default(1.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
