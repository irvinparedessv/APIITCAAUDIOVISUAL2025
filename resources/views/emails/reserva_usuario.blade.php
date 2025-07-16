@component('mail::message')

# ✅ Tu reserva ha sido registrada exitosamente

Gracias por realizar tu solicitud. Estos son los detalles:

@component('mail::panel')
<span style="color: rgb(139, 0, 0); font-weight: bold;">
- Aula: {{ $reserva->aula }}<br>
- Fecha de inicio: {{ $reserva->fecha_reserva }}<br>
- Fecha de entrega: {{ $reserva->fecha_entrega }}
</span>
@endcomponent

### Equipos solicitados:
@foreach ($reserva->equipos as $equipo)
- {{ $equipo->nombre }}
@endforeach

@slot('subcopy')
<span style="margin-top: 30px; font-size: 13px; color: #999;">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
