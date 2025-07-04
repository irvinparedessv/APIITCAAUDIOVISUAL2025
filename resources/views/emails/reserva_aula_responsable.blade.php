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

# 📢 Nueva reserva de aula

Hola {{ $responsable->first_name }} {{ $responsable->last_name }},

El usuario **{{ $reserva->user->first_name }} {{ $reserva->user->last_name }}** ha reservado el aula **{{ $reserva->aula->name }}**.

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
