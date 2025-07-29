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

    // En tu BitacoraHelper.php, añade estos métodos:

public static function registrarActualizacionEquipo($equipo, $cambios, $caracteristicasCambiadas = [])
{
    $user = Auth::user();
    $tipo = $equipo->numero_serie ? 'Equipo' : 'Insumo';
    
    $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') . 
                   " actualizó el {$tipo} ID: {$equipo->id}";
    
    if (!empty($cambios)) {
        $descripcion .= "\nCambios:";
        foreach ($cambios as $campo => $valor) {
            $descripcion .= "\n- {$campo}: {$valor['anterior']} → {$valor['nuevo']}";
        }
    }
    
    if (!empty($caracteristicasCambiadas)) {
        $descripcion .= "\nCaracterísticas actualizadas:";
        foreach ($caracteristicasCambiadas as $caracteristica) {
            $descripcion .= "\n- {$caracteristica['nombre']}: {$caracteristica['anterior']} → {$caracteristica['nuevo']}";
        }
    }

    Bitacora::create([
        'user_id' => $user?->id,
        'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
        'accion' => 'Actualización de ' . $tipo,
        'modulo' => 'Inventario',
        'descripcion' => $descripcion,
    ]);
}

public static function detectarCambios($original, $actualizado, $excluir = ['updated_at'])
{
    $cambios = [];
    
    foreach ($actualizado as $key => $value) {
        if (in_array($key, $excluir)) continue;
        
        if (array_key_exists($key, $original) && $original[$key] != $value) {
            $cambios[$key] = [
                'anterior' => $original[$key],
                'nuevo' => $value
            ];
        }
    }
    
    return $cambios;
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
