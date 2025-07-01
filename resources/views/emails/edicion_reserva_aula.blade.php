@component('mail::message')

@if ($esResponsable)
# ¡Reserva de aula modificada!

Se ha realizado una modificación en la reserva de aula del usuario **{{ $reserva->user->first_name }} {{ $reserva->user->last_name }}**. Aquí están los detalles actualizados:

@else
# ¡Tu reserva ha sido modificada!

Hola **{{ $usuario->first_name }} {{ $usuario->last_name }}**,

La información de tu reserva de aula ha sido actualizada. Aquí están los nuevos detalles:
@endif

@component('mail::panel')
**Aula:** {{ $reserva->aula->name }}  
**Fecha:** {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}  
**Horario:** {{ $reserva->horario }}  
**Estado:** {{ $reserva->estado }}  
@isset($reserva->comentario)
**Comentario:** {{ $reserva->comentario }}
@endisset
@endcomponent

@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot

@endcomponent
