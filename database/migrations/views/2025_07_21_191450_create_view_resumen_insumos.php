<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateViewResumenInsumos extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW vista_resumen_insumos AS
            SELECT 
                m.nombre AS nombre_modelo,
                COUNT(e.id) AS cantidad_total,
                SUM(CASE WHEN est.nombre = 'Disponible' THEN 1 ELSE 0 END) AS cantidad_disponible,
                SUM(CASE WHEN est.nombre = 'Dañado' THEN 1 ELSE 0 END) AS cantidad_eliminada
            FROM equipos e
            JOIN modelos m ON m.id = e.modelo_id
            JOIN estados est ON est.id = e.estado_id
            WHERE e.es_componente = 1
            GROUP BY e.modelo_id, m.nombre
            ORDER BY m.nombre
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vista_resumen_insumos");
    }
}
