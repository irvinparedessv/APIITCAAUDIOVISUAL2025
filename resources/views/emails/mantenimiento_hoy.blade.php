@component('mail::message')
# Mantenimiento programado para hoy

Hola {{ $mantenimiento->user->first_name ?? 'usuario' }} {{ $mantenimiento->user->last_name }}

Tienes un mantenimiento programado para hoy, estos son los detalles:

- **Equipo:** 
  @if($mantenimiento->equipo->modelo && $mantenimiento->equipo->modelo->marca)
    {{ $mantenimiento->equipo->modelo->marca->nombre }} {{ $mantenimiento->equipo->modelo->nombre }}
  @else
    {{ $mantenimiento->equipo->numero_serie ?? 'N/A' }} (sin modelo/marca)
  @endif
- **Tipo:** {{ $mantenimiento->tipoMantenimiento->nombre ?? 'N/A' }}
- **Hora de inicio:** {{ $mantenimiento->hora_mantenimiento_inicio }}

Por favor, realiza el mantenimiento según lo programado.

Gracias,<br>
@endcomponent