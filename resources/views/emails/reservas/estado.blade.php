@php
$estadoColor = match($reserva->estado) {
'Aprobado' => '✅ Aprobada',
'Rechazado' => '❌ Rechazada',
'Devuelto' => '🔁 Devuelta',
'Cancelado' => '❌ Cancelado',
default => ucfirst($reserva->estado),
};
@endphp

@component('mail::message')
# Estado de Reserva Actualizado

Se ha actualizado una reserva realizada por **{{ $reserva->user->first_name }} {{ $reserva->user->last_name }}**.

@component('mail::panel')
**Aula:** {{ $reserva->aula->name ?? 'Sin aula' }}<br>
**Fecha de reserva:** {{ $reserva->fecha_reserva }}<br>
**Fecha de entrega:** {{ $reserva->fecha_entrega }}<br>
**Nuevo estado:** {{ $estadoColor }}<br>
@if($reserva->comentario)
**Comentario:** {{ $reserva->comentario }}
@endif
@endcomponent

Gracias.
@endcomponent