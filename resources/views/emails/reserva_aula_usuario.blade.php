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

# ✅ Reserva confirmada

Hola **{{ $usuario->first_name }} {{ $usuario->last_name }}**,

Tu reserva del aula **{{ $reserva->aula->name }}** fue registrada correctamente.

@component('mail::panel')
<span style="color: rgb(139, 0, 0); font-weight: bold;">
📅 Fecha: {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}<br>
⏰ Horario: {{ $reserva->horario }}
</span>
@endcomponent

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
