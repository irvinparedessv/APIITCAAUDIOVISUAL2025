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

# Confirmación de Cuenta - {{ config('app.name') }}

Hola {{ $user->first_name }} {{ $user->last_name }},

Se ha creado tu cuenta con las siguientes credenciales temporales:

@component('mail::panel')
<span style="color: rgb(139, 0, 0); font-weight: bold;">
->Email: {{ $user->email }}<br>
->Contraseña Temporal: {{ $password }}
</span>
@endcomponent

**Por favor sigue estos pasos:**
<ol>
    <li>Haz clic en el botón para confirmar tu cuenta</li>
    <li>Inicia sesión con las credenciales temporales</li>
    <li>Establece una nueva contraseña segura</li>
</ol>

<div style="text-align: center;">
    <a href="{{ $confirmationUrl }}" class="custom-button">Confirmar Mi Cuenta</a>
</div>

@component('mail::subcopy')
Si tienes problemas con el botón, copia y pega esta URL en tu navegador:  
<p style="word-break: break-all; color: rgb(139, 0, 0);">
    <a href="{{ $confirmationUrl }}" style="color: rgb(139, 0, 0);">{{ $confirmationUrl }}</a>
</p>
@endcomponent

Gracias.

@slot('subcopy')
<span class="footer">
Este mensaje ha sido generado automáticamente. Por favor, no respondas a este correo.
</span>
@endslot

@endcomponent
