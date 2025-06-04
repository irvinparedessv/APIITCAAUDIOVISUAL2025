@component('mail::message')
# 🔄 Estado de tu reserva actualizado

Hola **{{ $usuario->first_name }} {{ $usuario->last_name }}**,

El estado de tu reserva del aula **{{ $reserva->aula->name }}** ha sido actualizado a:

@component('mail::panel')
📅 **Fecha:** {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}  
⏰ **Horario:** {{ $reserva->horario }}  
📌 **Estado actual:** {{ strtoupper($reserva->estado) }}  
@if ($reserva->comentario)
📝 **Comentario:** {{ $reserva->comentario }}
@endif
@endcomponent

@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot
@endcomponent
