<h2>Nueva reserva recibida</h2>

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
