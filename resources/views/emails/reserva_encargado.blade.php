@component('mail::message')
{{-- Estilos inline para mayor compatibilidad con clientes de correo --}}
<style>
    h2 {
        font-family: 'Georgia', serif;
        color: rgb(139, 0, 0); /* Color principal */
    }
    p {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #333;
        font-size: 16px;
        line-height: 1.6;
    }
    ul {
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

{{-- Logo personalizado --}}
<img src="{{ asset('storage/logo.png') }}" alt="Mi Logo" style="max-width: 200px; margin-bottom: 20px;"/>

# Nueva reserva recibida

<p><strong>Usuario:</strong> {{ $reserva->user->first_name }} {{ $reserva->user->last_name }}</p>
<p><strong>Aula:</strong> {{ $reserva->aula }}</p>
<p><strong>Fecha de inicio:</strong> {{ $reserva->fecha_reserva }}</p>
<p><strong>Fecha de entrega:</strong> {{ $reserva->fecha_entrega }}</p>
<p><strong>Equipos:</strong></p>
<ul>
    @foreach ($reserva->equipos as $equipo)
        <li>{{ $equipo->nombre }}</li>
    @endforeach
</ul>

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
