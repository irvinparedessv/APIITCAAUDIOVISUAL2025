<?php

namespace App\Services;

use App\Models\Equipo;
use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;
use App\Models\ReservaEquipo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PrediccionEquipoService
{
    public function predecirReservasMensuales(int $mesesAPredecir = 6, int $tipoEquipoId = null)
    {
        // Obtener datos históricos
        [$datosHistoricos, $primerMesReal] = $tipoEquipoId
            ? $this->obtenerDatosHistoricosPorTipo($tipoEquipoId)
            : $this->obtenerDatosHistoricos();

        if (count($datosHistoricos) < 3) {
            throw new \Exception("No hay suficientes datos históricos para realizar la predicción (mínimo 3 meses requeridos)");
        }

        // Filtrar meses con 0 reservas intermedios
        $ultimoMes = max(array_keys($datosHistoricos));
        $datosFiltrados = array_filter($datosHistoricos, function ($data, $mes) use ($ultimoMes) {
            return $data['total'] > 0 || $mes === 0 || $mes === $ultimoMes;
        }, ARRAY_FILTER_USE_BOTH);

        // Preparar datos para entrenamiento
        $samples = [];
        $targets = [];

        foreach ($datosFiltrados as $mes => $data) {
            $samples[] = [$mes];
            $targets[] = $data['total'];
        }

        if (count($samples) < 3) {
            throw new \Exception("Los datos filtrados no son suficientes para una predicción confiable.");
        }

        // Entrenar modelos
        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        // Ajustar SVR automáticamente si hay pocos datos
        $usarSVR = count($samples) >= 6;
        $predicciones = [];

        if ($usarSVR) {
            $svr = new SVR(Kernel::RBF, 10.0, 0.001, 0.1, 0.001, 100);
            $svr->train($samples, $targets);
        }

        // Generar predicciones
        $ultimoMesEntrenado = max(array_keys($datosFiltrados));

        for ($i = 1; $i <= $mesesAPredecir; $i++) {
            $mesPrediccion = $ultimoMesEntrenado + $i;
            $prediccionRL = max(0, $regresionLineal->predict([$mesPrediccion]));
            $prediccionSVR = $usarSVR ? max(0, $svr->predict([$mesPrediccion])) : $prediccionRL;
            $promedio = ($prediccionRL + $prediccionSVR) / 2;

            $predicciones[$mesPrediccion] = [
                'prediccion' => round($promedio),
                'regresion_lineal' => round($prediccionRL),
                'svr' => round($prediccionSVR),
                'mes' => $this->convertirNumeroAMes($mesPrediccion, $primerMesReal),
            ];
        }

        // Calcular precisión
        $precision = $this->evaluarModelo($regresionLineal, $samples, $targets);
        // $confiabilidad = $this->evaluarConfiabilidad($precision, count($samples));

        // Logging
        Log::info("Predicción por tipo equipo", [
            'tipo_equipo_id' => $tipoEquipoId,
            'muestras_utilizadas' => count($samples),
            'precision' => $precision,
        ]);

        return [
            'historico' => $datosFiltrados,
            'predicciones' => $predicciones,
            'precision' => round($precision, 2),
        ];
    }


    protected function obtenerDatosHistoricos(): array
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $reservasPorMes = ReservaEquipo::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->whereIn('estado', ['Aprobado', 'Completado'])
            ->selectRaw('YEAR(fecha_reserva) as year, MONTH(fecha_reserva) as month, SUM(equipo_reserva.cantidad) as total')
            ->join('equipo_reserva', 'reserva_equipos.id', '=', 'equipo_reserva.reserva_equipo_id')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $this->procesarDatosHistoricos($reservasPorMes);
    }

    protected function obtenerDatosHistoricosPorTipo(int $tipoEquipoId): array
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $reservasPorMes = ReservaEquipo::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->whereIn('reserva_equipos.estado', ['Aprobado', 'Completado'])
            ->join('equipo_reserva', 'reserva_equipos.id', '=', 'equipo_reserva.reserva_equipo_id')
            ->join('equipos', 'equipo_reserva.equipo_id', '=', 'equipos.id')
            ->where('equipos.tipo_equipo_id', $tipoEquipoId)
            ->selectRaw('YEAR(fecha_reserva) as year, MONTH(fecha_reserva) as month, SUM(equipo_reserva.cantidad) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $this->procesarDatosHistoricos($reservasPorMes);
    }


    protected function procesarDatosHistoricos($reservasPorMes): array
    {
        $datos = [];
        $primerMes = null;
        $primerMesReal = null;

        foreach ($reservasPorMes as $reserva) {
            $mesKey = $reserva->year * 12 + ($reserva->month - 1);

            if ($primerMes === null) {
                $primerMes = $mesKey;
                $primerMesReal = Carbon::createFromDate($reserva->year, $reserva->month, 1);
            }

            $mesSecuencial = $mesKey - $primerMes;
            $datos[$mesSecuencial] = [
                'total' => (int) $reserva->total,
                'year' => $reserva->year,
                'month' => $reserva->month,
                'mes_nombre' => $this->convertirNumeroAMes($mesSecuencial, $primerMesReal),
            ];
        }

        return [$datos, $primerMesReal];
    }

    protected function evaluarModelo($modelo, $samples, $targets): float
    {
        $errores = [];

        foreach ($samples as $i => $sample) {
            $real = $targets[$i];
            $prediccion = $modelo->predict($sample);

            // MAE: Error absoluto
            $errorAbsoluto = abs($prediccion - $real);
            $errores[] = $errorAbsoluto;
        }

        if (count($errores) === 0) {
            return 0;
        }

        $mae = array_sum($errores) / count($errores);

        // Escalamos la precisión en base a la media de los valores reales
        $mediaReal = array_sum($targets) / count($targets);
        $precision = max(0, 1 - ($mae / max(1, $mediaReal))) * 100;

        return round($precision, 2);
    }




    protected function convertirNumeroAMes(int $mesOffset, Carbon $inicio): string
    {
        return $inicio->copy()->addMonths($mesOffset)->format('M Y'); // Ej: Ene 2024
    }

    public function predecirReservasMensualesPorEquipo(int $mesesAPredecir = 6, int $equipoId)
    {
        // Obtener datos históricos
        [$datosHistoricos, $primerMesReal] = $this->obtenerDatosHistoricosPorEquipo($equipoId);

        if (count($datosHistoricos) < 5) {
            throw new \Exception("No hay suficientes datos históricos para el equipo ID {$equipoId} (mínimo 5 meses requeridos)");
        }

        // No filtrar meses en 0: se mantienen todos
        $datosFiltrados = $datosHistoricos;

        // Preparar datos usando ventana móvil de 2 meses
        $samples = [];
        $targets = [];
        $valores = array_values($datosFiltrados);

        for ($i = 2; $i < count($valores); $i++) {
            $samples[] = [
                $valores[$i - 2]['total'],
                $valores[$i - 1]['total']
            ];
            $targets[] = $valores[$i]['total'];
        }

        if (count($samples) < 3) {
            throw new \Exception("Los datos del equipo ID {$equipoId} no son suficientes para una predicción confiable.");
        }

        // Entrenar modelo de regresión lineal
        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        // Entrenar SVR si hay suficientes datos
        $usarSVR = count($samples) >= 6;
        if ($usarSVR) {
            $svr = new SVR(Kernel::RBF, 10.0, 0.001, 0.1, 0.001, 100);
            $svr->train($samples, $targets);
        }

        // Predicción con ventana móvil
        $input1 = $valores[count($valores) - 2]['total'];
        $input2 = $valores[count($valores) - 1]['total'];
        $ultimoMesEntrenado = max(array_keys($datosFiltrados));

        $predicciones = [];

        for ($i = 1; $i <= $mesesAPredecir; $i++) {
            $input = [$input1, $input2];

            $predRL = max(0, $regresionLineal->predict($input));
            $predSVR = $usarSVR ? max(0, $svr->predict($input)) : $predRL;
            $promedio = ($predRL + $predSVR) / 2;

            $predicciones[$ultimoMesEntrenado + $i] = [
                'prediccion' => round($promedio),
                'regresion_lineal' => round($predRL),
                'svr' => round($predSVR),
                'mes' => $this->convertirNumeroAMes($ultimoMesEntrenado + $i, $primerMesReal),
            ];

            // Avanzar la ventana
            $input1 = $input2;
            $input2 = round($promedio);
        }

        // Evaluar precisión con regresión lineal
        $precision = $this->evaluarModelo($regresionLineal, $samples, $targets);

        // Logging
        Log::info("Predicción mejorada por equipo", [
            'equipo_id' => $equipoId,
            'muestras_utilizadas' => count($samples),
            'precision' => $precision,
        ]);

        return [
            'historico' => $datosFiltrados,
            'predicciones' => $predicciones,
            'precision' => round($precision, 2),
        ];
    }



    protected function obtenerDatosHistoricosPorEquipo(int $equipoId): array
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $reservasPorMes = ReservaEquipo::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->whereIn('reserva_equipos.estado', ['Aprobado', 'Completado'])
            ->join('equipo_reserva', 'reserva_equipos.id', '=', 'equipo_reserva.reserva_equipo_id')
            ->where('equipo_reserva.equipo_id', $equipoId) // filtro por equipo aquí
            ->selectRaw('YEAR(fecha_reserva) as year, MONTH(fecha_reserva) as month, SUM(equipo_reserva.cantidad) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $this->procesarDatosHistoricos($reservasPorMes);
    }









    public function predecirVidaUtil(int $equipoId, int $mesesAPredecir = 12)
    {
        // Obtener datos del equipo
        $equipo = Equipo::findOrFail($equipoId);
        $vidaUtilTotal = $equipo->vida_util ?? 20000;

        // Obtener datos históricos de horas usadas por mes
        [$datosHistoricos, $primerMesReal] = $this->obtenerHorasUsoPorEquipo($equipoId);

        if (count($datosHistoricos) < 3) {
            throw new \Exception("No hay suficientes datos históricos para el equipo ID {$equipoId} (mínimo 3 meses requeridos)");
        }

        // Calcular vida útil acumulada
        $vidaUtilRestante = $vidaUtilTotal;
        $datosConVidaUtil = [];
        $horasAcumuladas = 0;

        foreach ($datosHistoricos as $mes => $data) {
            $horasUsadas = $data['total_horas'];
            $horasAcumuladas += $horasUsadas;
            $vidaUtilRestante = max(0, $vidaUtilTotal - $horasAcumuladas);

            $datosConVidaUtil[$mes] = [
                'horas_usadas' => $horasUsadas,
                'horas_acumuladas' => $horasAcumuladas,
                'vida_util_restante' => $vidaUtilRestante,
                'porcentaje_utilizado' => round(($horasAcumuladas / $vidaUtilTotal) * 100, 2),
                'year' => $data['year'],
                'month' => $data['month'],
                'mes_nombre' => $data['mes_nombre'],
                'tipo' => 'Histórico',
                'cantidad_reservas' => $data['cantidad_reservas'] ?? 0
            ];
        }

        // Preparar datos para entrenamiento (predicción de horas a usar)
        $samples = [];
        $targets = [];

        foreach ($datosHistoricos as $mes => $data) {
            $samples[] = [$mes];
            $targets[] = $data['total_horas'];
        }

        // Entrenar modelos
        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        $usarSVR = count($samples) >= 6;
        if ($usarSVR) {
            $svr = new SVR(Kernel::RBF, 10.0, 0.001, 0.1, 0.001, 100);
            $svr->train($samples, $targets);
        }

        // Generar predicciones
        $ultimoMesEntrenado = max(array_keys($datosConVidaUtil));
        $predicciones = [];
        $horasAcumuladasPredichas = $horasAcumuladas;

        for ($i = 1; $i <= $mesesAPredecir; $i++) {
            $mesPrediccion = $ultimoMesEntrenado + $i;
            $prediccionRL = max(0, $regresionLineal->predict([$mesPrediccion]));
            $prediccionSVR = $usarSVR ? max(0, $svr->predict([$mesPrediccion])) : $prediccionRL;
            $horasPredichas = ($prediccionRL + $prediccionSVR) / 2;

            $horasAcumuladasPredichas += $horasPredichas;
            $vidaUtilRestantePredicha = max(0, $vidaUtilTotal - $horasAcumuladasPredichas);

            $predicciones[$mesPrediccion] = [
                'horas_usadas' => round($horasPredichas),
                'horas_acumuladas' => round($horasAcumuladasPredichas),
                'vida_util_restante' => round($vidaUtilRestantePredicha),
                'porcentaje_utilizado' => round(($horasAcumuladasPredichas / $vidaUtilTotal) * 100, 2),
                'mes' => $this->convertirNumeroAMes($mesPrediccion, $primerMesReal),
                'tipo' => 'Predicción',
                'regresion_lineal' => round($prediccionRL),
                'svr' => round($prediccionSVR),
                'cantidad_reservas' => null // No aplica para predicciones
            ];

            if ($horasAcumuladasPredichas >= $vidaUtilTotal) {
                break; // No predecir más allá de la vida útil
            }
        }

        // Calcular precisión
        $precision = $this->evaluarModelo($regresionLineal, $samples, $targets);

        // Calcular fecha estimada de fin de vida útil
        $mesesRestantes = count($predicciones);
        $fechaFinVidaUtil = $mesesRestantes > 0
            ? $primerMesReal->copy()->addMonths($ultimoMesEntrenado + $mesesRestantes)->format('M Y')
            : 'Ya alcanzó su vida útil';

        return [
            'equipo' => [
                'id' => $equipo->id,
                'nombre' => $equipo->marca_modelo,
                'numero_serie' => $equipo->numero_serie,
                'vida_util_total' => $vidaUtilTotal,
                'horas_acumuladas' => $horasAcumuladas,
                'porcentaje_utilizado' => round(($horasAcumuladas / $vidaUtilTotal) * 100, 2),
                'total_reservas' => array_sum(array_column($datosConVidaUtil, 'cantidad_reservas'))
            ],
            'historico' => array_values($datosConVidaUtil),
            'predicciones' => array_values($predicciones),
            'precision' => round($precision, 2),
            'meses_restantes' => $mesesRestantes,
            'fecha_fin_vida_util' => $fechaFinVidaUtil,
        ];
    }

    protected function obtenerHorasUsoPorEquipo(int $equipoId): array
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $horasPorMes = ReservaEquipo::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->whereIn('reserva_equipos.estado', ['Aprobado', 'Completado'])
            ->join('equipo_reserva', 'reserva_equipos.id', '=', 'equipo_reserva.reserva_equipo_id')
            ->where('equipo_reserva.equipo_id', $equipoId)
            ->selectRaw(
                'YEAR(fecha_reserva) as year, ' .
                    'MONTH(fecha_reserva) as month, ' .
                    'SUM(TIMESTAMPDIFF(HOUR, fecha_reserva, fecha_entrega)) as total_horas, ' .
                    'COUNT(reserva_equipos.id) as cantidad_reservas'
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $this->procesarDatosHistoricosHoras($horasPorMes, 'total_horas');
    }

    protected function procesarDatosHistoricosHoras($datosPorMes, $campoTotal = 'total_horas'): array
    {
        $datos = [];
        $primerMes = null;
        $primerMesReal = null;

        foreach ($datosPorMes as $dato) {
            $mesKey = $dato->year * 12 + ($dato->month - 1);

            if ($primerMes === null) {
                $primerMes = $mesKey;
                $primerMesReal = Carbon::createFromDate($dato->year, $dato->month, 1);
            }

            $mesSecuencial = $mesKey - $primerMes;
            $datos[$mesSecuencial] = [
                $campoTotal => (int) $dato->{$campoTotal},
                'year' => $dato->year,
                'month' => $dato->month,
                'mes_nombre' => $this->convertirNumeroAMes($mesSecuencial, $primerMesReal),
                'cantidad_reservas' => $dato->cantidad_reservas ?? 0
            ];
        }

        return [$datos, $primerMesReal];
    }
}
