<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
        CREATE OR REPLACE VIEW vista_equipos_con_modelo_aula AS
SELECT
    re.aula_id AS id_aula,
    GROUP_CONCAT(e.modelo_id) AS modelos_id,
    re.path_model
FROM reserva_equipos re
INNER JOIN equipo_reserva er ON er.reserva_equipo_id = re.id
INNER JOIN equipos e ON er.equipo_id = e.id
WHERE
    re.aula_id IS NOT NULL
    AND re.path_model IS NOT NULL
    AND re.path_model != ''
    AND e.es_componente = false
GROUP BY re.aula_id, re.path_model
;
");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vista_equipos_con_modelo_aula");
    }
};
