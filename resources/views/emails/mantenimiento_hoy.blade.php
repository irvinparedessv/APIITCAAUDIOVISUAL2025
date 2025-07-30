@component('mail::message')
# Mantenimiento programado para hoy

Hola {{ $mantenimiento->user->name ?? 'usuario' }},

Tienes un mantenimiento programado para hoy, estos son los detalles:

- **Equipo:** {{ $mantenimiento->equipo->nombre ?? 'N/A' }} (ID: {{ $mantenimiento->equipo_id }})
- **Tipo:** {{ $mantenimiento->tipoMantenimiento->nombre ?? 'N/A' }}
- **Hora de inicio:** {{ $mantenimiento->hora_mantenimiento_inicio }}
- **Hora final:** {{ $mantenimiento->hora_mantenimiento_final }}

Por favor, realiza el mantenimiento según lo programado.

Gracias,<br>
{{ config('app.name') }}
@endcomponent