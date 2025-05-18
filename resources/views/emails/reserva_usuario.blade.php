@component('mail::message')
{{-- Estilos inline para mejor compatibilidad con clientes de correo --}}
<div style="font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 16px; line-height: 1.6;">

    <h2 style="font-family: 'Georgia', serif; color: rgb(139, 0, 0); font-size: 24px; margin-bottom: 20px;">
        ✅ Tu reserva ha sido registrada exitosamente
    </h2>

    <p style="margin-bottom: 20px;">
        ¡Gracias por realizar tu solicitud! A continuación, te mostramos los detalles de tu reserva:
    </p>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px; background-color: #f9f9f9; border: 1px solid #e0e0e0;"><strong>Aula:</strong></td>
            <td style="padding: 10px; border: 1px solid #e0e0e0;">{{ $reserva->aula }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; background-color: #f9f9f9; border: 1px solid #e0e0e0;"><strong>Fecha de inicio:</strong></td>
            <td style="padding: 10px; border: 1px solid #e0e0e0;">{{ $reserva->fecha_reserva }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; background-color: #f9f9f9; border: 1px solid #e0e0e0;"><strong>Fecha de entrega:</strong></td>
            <td style="padding: 10px; border: 1px solid #e0e0e0;">{{ $reserva->fecha_entrega }}</td>
        </tr>
    </table>

    <p><strong>Equipos solicitados:</strong></p>
    <ul style="padding-left: 20px; margin-top: 10px;">
        @foreach ($reserva->equipos as $equipo)
            <li>{{ $equipo->nombre }}</li>
        @endforeach
    </ul>

</div>

@slot('subcopy')
<span style="margin-top: 30px; display: block; font-size: 13px; color: #999; text-align: center;">
    Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot
@endcomponent
