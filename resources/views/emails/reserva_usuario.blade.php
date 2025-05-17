<h2>Tu reserva ha sido registrada exitosamente</h2>

<p>Gracias por realizar tu solicitud. Estos son los detalles:</p>

<p><strong>Aula:</strong> {{ $reserva->aula }}</p>
<p><strong>Fecha de inicio:</strong> {{ $reserva->fecha_reserva }}</p>
<p><strong>Fecha de entrega:</strong> {{ $reserva->fecha_entrega }}</p>
<p><strong>Equipos solicitados:</strong></p>
<ul>
    @foreach ($reserva->equipos as $equipo)
        <li>{{ $equipo->nombre }}</li>
    @endforeach
</ul>
