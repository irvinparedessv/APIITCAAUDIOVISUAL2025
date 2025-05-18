@component('mail::message')
# Tu reserva ha sido registrada exitosamente

Gracias por realizar tu solicitud. Estos son los detalles:

- **Aula:** {{ $reserva->aula }}
- **Fecha de inicio:** {{ $reserva->fecha_reserva }}
- **Fecha de entrega:** {{ $reserva->fecha_entrega }}

### Equipos solicitados:

@foreach ($reserva->equipos as $equipo)
- {{ $equipo->nombre }}
@endforeach

@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot

@endcomponent
