<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateVistaEquiposVidaUtil extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE VIEW vista_equipos_vida_util AS
SELECT
    e.id AS equipo_id,
    e.numero_serie,
    m.nombre AS modelo_nombre,
    e.vida_util,
    COALESCE((
        SELECT SUM(
            TIMESTAMPDIFF(HOUR, er.fecha_inicio_reposo, er.fecha_fin_reposo)
        )
        FROM equipo_reserva er
        JOIN reserva_equipos re ON re.id = er.reserva_equipo_id
        WHERE er.equipo_id = e.id
            AND re.estado = 'Devuelto'
            AND er.fecha_inicio_reposo IS NOT NULL
            AND er.fecha_fin_reposo IS NOT NULL
    ), 0) AS tiempo_reserva_horas,
    COALESCE((
        SELECT SUM(mn.vida_util)
        FROM mantenimientos mn
        WHERE mn.equipo_id = e.id
    ), 0) AS vida_util_agregada_horas
FROM equipos e
LEFT JOIN modelos m ON m.id = e.modelo_id
WHERE e.es_componente = false;
");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS vista_equipos_vida_util');
    }
}
