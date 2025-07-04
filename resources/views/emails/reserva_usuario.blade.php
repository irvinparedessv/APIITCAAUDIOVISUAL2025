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
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
