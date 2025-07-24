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
                SUM(CASE WHEN est.nombre = 'En reparación' THEN 1 ELSE 0 END) AS cantidad_mantenimiento,
                SUM(CASE WHEN est.nombre = 'Dañado' THEN 1 ELSE 0 END) AS cantidad_eliminada,
                CASE
                    WHEN c.nombre = 'Equipo' THEN (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(mac.nombre, ' (', IFNULL(macm.nombre, 'Sin marca'), ')') SEPARATOR ' | ')
                        FROM modelo_accesorios ma
                        JOIN modelos mac ON mac.id = ma.modelo_insumo_id
                        LEFT JOIN marcas macm ON macm.id = mac.marca_id
                        WHERE ma.modelo_equipo_id = m.id
                    )
                    WHEN c.nombre = 'Insumo' THEN (
                        SELECT GROUP_CONCAT(DISTINCT te2.nombre SEPARATOR ' | ')
                        FROM modelo_accesorios ma2
                        JOIN equipos eq ON eq.modelo_id = ma2.modelo_equipo_id
                        JOIN tipo_equipos te2 ON te2.id = eq.tipo_equipo_id
                        WHERE ma2.modelo_insumo_id = m.id
                    )
                    ELSE NULL
                END AS accesorios_completos
            FROM equipos e
            JOIN modelos m ON m.id = e.modelo_id
            JOIN tipo_equipos te ON te.id = e.tipo_equipo_id
            JOIN categorias c ON c.id = te.categoria_id
            JOIN marcas mar ON mar.id = m.marca_id
            JOIN estados est ON est.id = e.estado_id
            WHERE m.is_deleted = 0 AND e.is_deleted = 0
            GROUP BY m.id, c.nombre, te.nombre, mar.nombre, m.nombre
            ORDER BY m.nombre;
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS vista_resumen_inventario;");
    }
}
