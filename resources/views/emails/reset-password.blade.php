@component('mail::message')
# Hola {{ $user->name }}

Has solicitado restablecer tu contraseña. Haz clic en el botón de abajo:

@component('mail::button', ['url' => $url])
Restablecer contraseña
@endcomponent

Si no hiciste esta solicitud, puedes ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
