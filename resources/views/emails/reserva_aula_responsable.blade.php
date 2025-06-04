@component('mail::message')
# 📢 Nueva reserva de aula

Hola **{{ $responsable->first_name }} {{ $responsable->last_name }}**,

El usuario **{{ $reserva->user->first_name }} {{ $reserva->user->last_name }}** ha reservado el aula **{{ $reserva->aula->name }}**.

@component('mail::panel')
- 📅 **Fecha:** {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}  
- ⏰ **Horario:** {{ $reserva->horario }}
@endcomponent

@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot
@endcomponent
