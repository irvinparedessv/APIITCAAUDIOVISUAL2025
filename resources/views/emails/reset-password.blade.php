@component('mail::message')
{{-- Estilos inline para mejor compatibilidad con clientes de correo --}}
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
    .custom-button {
        background-color: rgb(139, 0, 0); /* Color principal */
        color: #fff !important;
        padding: 12px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        display: inline-block;
        text-align: center;
        margin: 20px 0;
    }
</style>

# Estimado/a {{ $user->first_name }} {{ $user->last_name }},

Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Para continuar con el proceso, haz clic en el siguiente botón:

<div style="text-align: center;">
    <a href="{{ $url }}" class="custom-button">Restablecer Contraseña</a>
</div>

> **Este enlace será válido por 60 minutos.**

Si tú no realizaste esta solicitud, puedes ignorar este mensaje con total seguridad. Tu cuenta seguirá protegida.

Gracias por tu atención,<br>

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
