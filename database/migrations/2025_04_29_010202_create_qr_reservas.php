<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('codigo_qr_reserva_equipos', function (Blueprint $table) {
            $table->uuid('id')->primary(); // ID tipo UUID
            $table->foreignId('reserva_id')->references('id')->on('reserva_equipos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_qr_reserva');
    }
};
