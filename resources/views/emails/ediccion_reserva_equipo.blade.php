@component('mail::message')
# {{ $esResponsable ? 'Se ha actualizado una reserva de equipo' : 'Tu reserva de equipo ha sido modificada' }}

La reserva de equipo #{{ $reserva->id }} fue modificada.

@component('mail::panel')
- **Aula:** {{ $reserva->aula }}
- **Fecha Reserva:** {{ $reserva->fecha_reserva }}
- **Fecha Entrega:** {{ $reserva->fecha_entrega }}
- **Estado:** {{ $reserva->estado }}
@endcomponent

### Equipos reservados:
@foreach ($equipos as $equipo)
- **{{ $equipo['nombre'] }}** ({{ $equipo['tipo'] }}) - Cantidad: {{ $equipo['cantidad'] }}
@endforeach


@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot
@endcomponent
