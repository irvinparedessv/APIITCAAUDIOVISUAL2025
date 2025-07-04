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

# 🔄 Estado de reserva actualizado

Hola **{{ $usuario->first_name }} {{ $usuario->last_name }}**,

El estado de la reserva del aula **{{ $reserva->aula->name }}** ha sido actualizado a:

@component('mail::panel')
<span style="color: rgb(139, 0, 0); font-weight: bold;">
📅 Fecha: {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}<br>
⏰ Horario: {{ $reserva->horario }}<br>
📌 Estado actual: {{ strtoupper($reserva->estado) }}<br>
@if ($reserva->comentario)
📝 Comentario: {{ $reserva->comentario }}
@endif
</span>
@endcomponent

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
