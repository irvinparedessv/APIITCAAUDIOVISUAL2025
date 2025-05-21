@component('mail::message')
# Nueva reserva recibida

**Usuario:** {{ $reserva->user->first_name }} {{ $reserva->user->last_name }}

**Aula:** {{ $reserva->aula }}

**Fecha de inicio:** {{ $reserva->fecha_reserva }}

**Fecha de entrega:** {{ $reserva->fecha_entrega }}

**Equipos:**

@foreach ($reserva->equipos as $equipo)
- {{ $equipo->nombre }}
@endforeach

@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot
@endcomponent
