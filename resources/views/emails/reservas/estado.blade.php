@php
$estadoColor = match($reserva->estado) {
'Aprobado' => '✅ Aprobada',
'Rechazado' => '❌ Rechazada',
'Devuelto' => '🔁 Devuelta',
default => ucfirst($reserva->estado),
};
@endphp

@component('mail::message')
# Estado de Reserva Actualizado

Se ha actualizado una reserva realizada por **{{ $reserva->user->first_name }} {{ $reserva->user->last_name }}**.

@component('mail::panel')
**Aula:** {{ $reserva->aula }}
**Fecha de reserva:** {{ $reserva->fecha_reserva }}
**Fecha de entrega:** {{ $reserva->fecha_entrega }}
**Nuevo estado:** {{ $estadoColor }}
@if($reserva->comentario)
**Comentario:** {{ $reserva->comentario }}
@endif
@endcomponent

Gracias,
{{ config('app.name') }}
@endcomponent