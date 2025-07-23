<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateViewResumenInventario extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE VIEW vista_resumen_inventario AS
            SELECT
                m.id AS modelo_id,
                c.nombre AS nombre_categoria,
                te.nombre AS nombre_tipo_equipo,
                mar.nombre AS nombre_marca,
                m.nombre AS nombre_modelo,
                COUNT(e.id) AS cantidad_total,
                SUM(CASE WHEN est.nombre = 'Disponible' THEN 1 ELSE 0 END) AS cantidad_disponible,
                SUM(CASE WHEN est.nombre = 'Dañado' THEN 1 ELSE 0 END) AS cantidad_eliminada,
                GROUP_CONCAT(CASE WHEN est.nombre = 'Disponible' THEN e.id END ORDER BY e.id ASC SEPARATOR ',') AS equipos_id_disponibles,
                GROUP_CONCAT(CASE WHEN est.nombre = 'Disponible' THEN e.numero_serie END ORDER BY e.id ASC SEPARATOR ',') AS series_disponibles
            FROM equipos e
            JOIN modelos m ON m.id = e.modelo_id
            JOIN tipo_equipos te ON te.id = e.tipo_equipo_id
            JOIN categorias c ON c.id = te.categoria_id
            JOIN marcas mar ON mar.id = m.marca_id
            JOIN estados est ON est.id = e.estado_id
            GROUP BY e.modelo_id, m.nombre, m.id, c.nombre, te.nombre, mar.nombre
            ORDER BY m.nombre;
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS vista_resumen_inventario;");
    }
}
