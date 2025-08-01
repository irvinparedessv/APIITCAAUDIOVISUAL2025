<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    Schema::create('marcas', function (Blueprint $table) {
        $table->id();
        $table->string('nombre')->unique();
        $table->boolean('is_deleted')->default(false);
        $table->timestamps();
    });
}

    public function down(): void {
        Schema::dropIfExists('marcas');
    }
};
