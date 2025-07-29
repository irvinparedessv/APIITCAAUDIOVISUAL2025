@component('mail::message')
# Alerta: Vida útil baja

**Equipo:** {{ $equipo->modelo_nombre }}
**Serie:** {{ $equipo->numero_serie }}
**ID:** {{ $equipo->equipo_id }}

**Vida útil restante:** {{ $vida_restante }} horas

@component('mail::panel')
Por favor, revisa y programa el mantenimiento o reemplazo de este equipo lo antes posible.
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent