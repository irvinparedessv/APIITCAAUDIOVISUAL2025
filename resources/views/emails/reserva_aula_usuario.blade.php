@component('mail::message')
# ✅ Reserva confirmada

Hola **{{ $usuario->first_name }} {{ $usuario->last_name }}**,

Tu reserva del aula **{{ $reserva->aula->name }}** fue registrada correctamente.

@component('mail::panel')
- 📅 **Fecha:** {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}  
- ⏰ **Horario:** {{ $reserva->horario }}
@endcomponent

@slot('subcopy')
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
@endslot

@endcomponent
