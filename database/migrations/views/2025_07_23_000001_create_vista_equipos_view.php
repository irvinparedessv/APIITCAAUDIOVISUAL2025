<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            CREATE VIEW vista_equipos AS
            SELECT 
                equipos.id AS equipo_id,
                equipos.modelo_id,
                equipos.numero_serie,
                modelos.nombre AS nombre_modelo,
                marcas.nombre AS nombre_marca,
                tipo_equipos.nombre AS tipo_equipo,
                tipo_equipo_id,
                equipos.tipo_reserva_id,
                estados.nombre AS estado,

                -- Imágenes, prioridad: equipo > modelo
                COALESCE(equipos.imagen_glb, modelos.imagen_glb) AS imagen_glb,
                COALESCE(equipos.imagen_normal, modelos.imagen_normal) AS imagen_normal

            FROM equipos
            JOIN modelos ON equipos.modelo_id = modelos.id
            JOIN marcas ON modelos.marca_id = marcas.id
            JOIN tipo_equipos ON equipos.tipo_equipo_id = tipo_equipos.id
            JOIN estados ON equipos.estado_id = estados.id
            WHERE equipos.is_deleted = false
              AND modelos.is_deleted = false
              AND marcas.is_deleted = false
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vista_equipos");
    }
};
