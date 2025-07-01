<?php

namespace App\Services;

use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;
use App\Models\ReservaEquipo;
use Carbon\Carbon;

class PrediccionEquipoService
{
    public function predecirReservasMensuales(int $mesesAPredecir = 6, int $tipoEquipoId = null)
    {
        // Obtener datos históricos y la fecha base real
        [$datosHistoricos, $primerMesReal] = $tipoEquipoId
            ? $this->obtenerDatosHistoricosPorTipo($tipoEquipoId)
            : $this->obtenerDatosHistoricos();

        if (count($datosHistoricos) < 3) {
            throw new \Exception("No hay suficientes datos históricos para realizar la predicción (mínimo 3 meses requeridos)");
        }

        // Preparar datos para el modelo
        $samples = [];
        $targets = [];

        foreach ($datosHistoricos as $mes => $data) {
            $samples[] = [$mes];
            $targets[] = $data['total'];
        }

        // Entrenar modelos
        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        // Ajuste de parámetros para SVR con kernel RBF
        $svr = new SVR(
            Kernel::RBF,
            10.0,    // C (mayor regularización para ajuste más fino)
            0.001,   // epsilon (más sensible a errores pequeños)
            0.1,     // gamma (kernel más localizado)
            0.001,   // tol
            100      // max_passes
        );
        $svr->train($samples, $targets);

        // Realizar predicciones
        $predicciones = [];
        $ultimoMes = max(array_keys($datosHistoricos));

        for ($i = 1; $i <= $mesesAPredecir; $i++) {
            $mesPrediccion = $ultimoMes + $i;
            $prediccionRL = max(0, $regresionLineal->predict([$mesPrediccion]));
            $prediccionSVR = max(0, $svr->predict([$mesPrediccion]));
            $prediccionFinal = ($prediccionRL + $prediccionSVR) / 2;

            $predicciones[$mesPrediccion] = [
                'prediccion' => round($prediccionFinal),
                'regresion_lineal' => round($prediccionRL),
                'svr' => round($prediccionSVR),
                'mes' => $this->convertirNumeroAMes($mesPrediccion, $primerMesReal),
            ];
        }

        return [
            'historico' => $datosHistoricos,
            'predicciones' => $predicciones,
            'precision' => $this->evaluarModelo($regresionLineal, $samples, $targets),
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
            ->whereHas('equipos', function ($query) use ($tipoEquipoId) {
                $query->where('tipo_equipo_id', $tipoEquipoId);
            })
            ->selectRaw('YEAR(fecha_reserva) as year, MONTH(fecha_reserva) as month, SUM(equipo_reserva.cantidad) as total')
            ->join('equipo_reserva', 'reserva_equipos.id', '=', 'equipo_reserva.reserva_equipo_id')
            ->join('equipos', 'equipo_reserva.equipo_id', '=', 'equipos.id')
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
        $count = count($samples);
        $trainingSize = (int)($count * 0.8);

        for ($i = $trainingSize; $i < $count; $i++) {
            $prediccion = $modelo->predict([$samples[$i][0]]);
            $errores[] = abs($prediccion - $targets[$i]) / max(1, $targets[$i]);
        }

        $errorRelativo = array_sum($errores) / count($errores);
        return max(0, 1 - $errorRelativo) * 100;
    }

    protected function convertirNumeroAMes(int $mesOffset, Carbon $inicio): string
    {
        return $inicio->copy()->addMonths($mesOffset)->format('M Y'); // Ej: Ene 2024
    }

    public function predecirReservasMensualesPorEquipo(int $mesesAPredecir = 6, int $equipoId)
    {
        // Obtener datos históricos filtrados por equipo
        [$datosHistoricos, $primerMesReal] = $this->obtenerDatosHistoricosPorEquipo($equipoId);

        if (count($datosHistoricos) < 3) {
            throw new \Exception("No hay suficientes datos históricos para el equipo ID {$equipoId} (mínimo 3 meses requeridos)");
        }

        // Preparar datos
        $samples = [];
        $targets = [];

        foreach ($datosHistoricos as $mes => $data) {
            $samples[] = [$mes];
            $targets[] = $data['total'];
        }

        // Entrenar modelos
        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        // Ajuste de parámetros para SVR con kernel RBF
        $svr = new SVR(
            Kernel::RBF,
            10.0,    // C
            0.001,   // epsilon
            0.1,     // gamma
            0.001,   // tol
            100      // max_passes
        );
        $svr->train($samples, $targets);

        // Generar predicciones
        $predicciones = [];
        $ultimoMes = max(array_keys($datosHistoricos));

        for ($i = 1; $i <= $mesesAPredecir; $i++) {
            $mesPrediccion = $ultimoMes + $i;
            $prediccionRL = max(0, $regresionLineal->predict([$mesPrediccion]));
            $prediccionSVR = max(0, $svr->predict([$mesPrediccion]));
            $prediccionFinal = ($prediccionRL + $prediccionSVR) / 2;

            $predicciones[$mesPrediccion] = [
                'prediccion' => round($prediccionFinal),
                'regresion_lineal' => round($prediccionRL),
                'svr' => round($prediccionSVR),
                'mes' => $this->convertirNumeroAMes($mesPrediccion, $primerMesReal),
            ];
        }

        return [
            'historico' => $datosHistoricos,
            'predicciones' => $predicciones,
            'precision' => $this->evaluarModelo($regresionLineal, $samples, $targets),
        ];
    }

    protected function obtenerDatosHistoricosPorEquipo(int $equipoId): array
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $reservasPorMes = ReservaEquipo::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->whereIn('reserva_equipos.estado', ['Aprobado', 'Completado'])
            ->whereHas('equipos', function ($query) use ($equipoId) {
                $query->where('equipos.id', $equipoId);
            })
            ->selectRaw('YEAR(fecha_reserva) as year, MONTH(fecha_reserva) as month, SUM(equipo_reserva.cantidad) as total')
            ->join('equipo_reserva', 'reserva_equipos.id', '=', 'equipo_reserva.reserva_equipo_id')
            ->join('equipos', 'equipo_reserva.equipo_id', '=', 'equipos.id')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $this->procesarDatosHistoricos($reservasPorMes);
    }
}
