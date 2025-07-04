@component('mail::message')
<style>
    h1 {
        font-family: 'Georgia', serif;
        color: rgb(139, 0, 0); /* Color principal */
    }
    p {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #333;
        font-size: 16px;
        line-height: 1.6;
    }
    .footer {
        margin-top: 30px;
        font-size: 13px;
        color: #999;
    }
</style>

# {{ $esResponsable ? 'Se ha actualizado una reserva de equipo' : 'Tu reserva de equipo ha sido modificada' }}

Hola,

La reserva de equipo #{{ $reserva->id }} fue modificada con la siguiente información:

@component('mail::panel')
<span style="color: rgb(139, 0, 0); font-weight: bold;">
- Aula: {{ $reserva->aula }}<br>
- Fecha Reserva: {{ $reserva->fecha_reserva }}<br>
- Fecha Entrega: {{ $reserva->fecha_entrega }}<br>
- Estado: {{ $reserva->estado }}
</span>
@endcomponent

### Equipos reservados:
@foreach ($equipos as $equipo)
- **{{ $equipo['nombre'] }}** ({{ $equipo['tipo'] }}) – Cantidad: {{ $equipo['cantidad'] }}
@endforeach

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot
@endcomponent
