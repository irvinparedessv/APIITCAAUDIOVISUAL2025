<?php

namespace App\Services;

use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;
use App\Models\ReservaAula;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PrediccionAulaService
{
    public function predecirReservasPorAula(int $aulaId, int $mesesAPredecir = 6)
    {
        [$datosHistoricos, $primerMesReal] = $this->obtenerDatosHistoricosPorAula($aulaId);

        if (count($datosHistoricos) < 5) {
            throw new \Exception("No hay suficientes datos históricos para el aula ID {$aulaId}.");
        }

        $datosFiltrados = $datosHistoricos;

        $samples = [];
        $targets = [];
        $valores = array_values($datosFiltrados);

        for ($i = 2; $i < count($valores); $i++) {
            $samples[] = [
                $valores[$i - 2]['total'],
                $valores[$i - 1]['total'],
            ];
            $targets[] = $valores[$i]['total'];
        }

        if (count($samples) < 3) {
            throw new \Exception("Los datos no son suficientes para una predicción confiable.");
        }

        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        $usarSVR = count($samples) >= 6;
        if ($usarSVR) {
            $svr = new SVR(Kernel::RBF, 10.0, 0.001, 0.1, 0.001, 100);
            $svr->train($samples, $targets);
        }

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

            $input1 = $input2;
            $input2 = round($promedio);
        }

        $precision = $this->evaluarModelo($regresionLineal, $samples, $targets);

        Log::info("Predicción por aula", [
            'aula_id' => $aulaId,
            'muestras_utilizadas' => count($samples),
            'precision' => $precision,
        ]);

        return [
            'historico' => $datosFiltrados,
            'predicciones' => $predicciones,
            'precision' => round($precision, 2),
        ];
    }

    protected function obtenerDatosHistoricosPorAula(int $aulaId): array
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $reservasPorMes = ReservaAula::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('aula_id', $aulaId)
            ->whereIn('estado', ['Aprobado', 'Completado'])
            ->selectRaw('YEAR(fecha) as year, MONTH(fecha) as month, COUNT(*) as total')
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

    protected function convertirNumeroAMes(int $mesOffset, Carbon $inicio): string
    {
        return $inicio->copy()->addMonths($mesOffset)->format('M Y');
    }

    protected function evaluarModelo($modelo, $samples, $targets): float
    {
        $errores = [];

        foreach ($samples as $i => $sample) {
            $real = $targets[$i];
            $prediccion = $modelo->predict($sample);
            $errores[] = abs($prediccion - $real);
        }

        if (count($errores) === 0) return 0;

        $mae = array_sum($errores) / count($errores);
        $mediaReal = array_sum($targets) / count($targets);
        $precision = max(0, 1 - ($mae / max(1, $mediaReal))) * 100;

        return round($precision, 2);
    }

    public function predecirReservasAulasGenerales(int $mesesAPredecir = 6)
    {
        $fechaInicio = Carbon::now()->subMonths(24);
        $fechaFin = Carbon::now();

        $reservasPorMes = ReservaAula::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereIn('estado', ['Aprobado', 'Completado'])
            ->selectRaw('YEAR(fecha) as year, MONTH(fecha) as month, COUNT(*) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        [$datosHistoricos, $primerMesReal] = $this->procesarDatosHistoricos($reservasPorMes);

        if (count($datosHistoricos) < 5) {
            throw new \Exception("No hay suficientes datos históricos generales.");
        }

        $samples = [];
        $targets = [];
        $valores = array_values($datosHistoricos);

        for ($i = 2; $i < count($valores); $i++) {
            $samples[] = [
                $valores[$i - 2]['total'],
                $valores[$i - 1]['total'],
            ];
            $targets[] = $valores[$i]['total'];
        }

        if (count($samples) < 3) {
            throw new \Exception("Los datos generales no son suficientes para una predicción confiable.");
        }

        $regresionLineal = new LeastSquares();
        $regresionLineal->train($samples, $targets);

        $usarSVR = count($samples) >= 6;
        if ($usarSVR) {
            $svr = new SVR(Kernel::RBF, 10.0, 0.001, 0.1, 0.001, 100);
            $svr->train($samples, $targets);
        }

        $input1 = $valores[count($valores) - 2]['total'];
        $input2 = $valores[count($valores) - 1]['total'];
        $ultimoMesEntrenado = max(array_keys($datosHistoricos));

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

            $input1 = $input2;
            $input2 = round($promedio);
        }

        $precision = $this->evaluarModelo($regresionLineal, $samples, $targets);

        Log::info("Predicción general de aulas", [
            'muestras_utilizadas' => count($samples),
            'precision' => $precision,
        ]);

        return [
            'historico' => $datosHistoricos,
            'predicciones' => $predicciones,
            'precision' => round($precision, 2),
        ];
    }
}
