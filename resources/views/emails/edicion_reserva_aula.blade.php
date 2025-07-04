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

@if ($esResponsable)
# ¡Reserva de aula modificada!

Se ha realizado una modificación en la reserva de aula del usuario **{{ $reserva->user->first_name }} {{ $reserva->user->last_name }}**. Aquí están los detalles actualizados:
@else
# ¡Tu reserva ha sido modificada!

Hola **{{ $usuario->first_name }} {{ $usuario->last_name }}**,

La información de tu reserva de aula ha sido actualizada. Aquí están los nuevos detalles:
@endif

@component('mail::panel')
<span style="color: rgb(139, 0, 0); font-weight: bold;">
Aula: {{ $reserva->aula->name }}<br>
Fecha: {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}<br>
Horario: {{ $reserva->horario }}<br>
Estado: {{ $reserva->estado }}<br>
@isset($reserva->comentario)
Comentario: {{ $reserva->comentario }}
@endisset
</span>
@endcomponent

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
