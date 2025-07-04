<?php

namespace App\Helpers;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

class BitacoraHelper
{
    public static function registrarCambioEstadoReserva(int $reservaId, string $estadoAnterior, string $estadoNuevo, string $nombrePrestamista)
    {
        $user = Auth::user();
        $estadoLegibleAnterior = self::mapearEstado($estadoAnterior);
        $estadoLegibleNuevo = self::mapearEstado($estadoNuevo);

        Bitacora::create([
            'user_id' => $user?->id,
            'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
            'accion' => 'Cambio de estado a ' . $estadoLegibleNuevo,
            'modulo' => 'Reserva Equipo',
            'descripcion' => ($user ? "{$user->first_name}" : 'Sistema') .
                " cambió el estado de la reserva de {$nombrePrestamista} de '{$estadoLegibleAnterior}' a '{$estadoLegibleNuevo}' (ID Reserva: {$reservaId})",
        ]);
    }

    public static function registrarCambioEstadoReservaAula(int $reservaId, string $estadoAnterior, string $estadoNuevo, string $nombrePrestamista)
    {
        $user = Auth::user();
        $estadoLegibleAnterior = self::mapearEstado($estadoAnterior);
        $estadoLegibleNuevo = self::mapearEstado($estadoNuevo);

        Bitacora::create([
            'user_id' => $user?->id,
            'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
            'accion' => 'Cambio de estado a ' . $estadoLegibleNuevo,
            'modulo' => 'Reserva Aula',
            'descripcion' => ($user ? "{$user->first_name}" : 'Sistema') .
                " cambió el estado de la reserva de aula de {$nombrePrestamista} de '{$estadoLegibleAnterior}' a '{$estadoLegibleNuevo}' (ID Reserva: {$reservaId})",
        ]);
    }


    private static function mapearEstado(string $estado): string
    {
        return match (strtolower($estado)) {
            'Aprobado', 'entregado' => 'Entregado',
            'Devuelto', 'devuelto' => 'Devuelto',
            'Rechazado', 'rechazado' => 'Rechazado',
            'Pendiente', 'pendiente' => 'Pendiente',
            default => ucfirst($estado)
        };
    }
}
