<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('cascade');
            $table->string('imagen_normal')->nullable(); // imagen propia
            $table->string('imagen_glb')->nullable();   // imagen para visualización global
            $table->boolean('is_deleted')->default(false);
            $table->integer('reposo')->nullable();
            $table->decimal('escala', 5, 2)->default(1.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos');
    }
};
