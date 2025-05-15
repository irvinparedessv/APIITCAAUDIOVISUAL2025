@component('mail::message')
# Confirmación de Cuenta - {{ config('app.name') }}

Hola {{ $user->first_name }} {{ $user->last_name }},

Se ha creado tu cuenta con las siguientes credenciales temporales:

@component('mail::panel')
**Email:** {{ $user->email }}  
**Contraseña Temporal:** {{ $password }}
@endcomponent

**Por favor sigue estos pasos:**
1. Haz clic en el botón para confirmar tu cuenta
2. Inicia sesión con las credenciales temporales
3. Establece una nueva contraseña segura

@component('mail::button', ['url' => $confirmationUrl, 'color' => 'primary'])
Confirmar Mi Cuenta
@endcomponent

@component('mail::subcopy')
Si tienes problemas con el botón, copia y pega esta URL en tu navegador:  
<p style="word-break: break-all;">
    <a href="{{ $confirmationUrl }}">{{ $confirmationUrl }}</a>
</p>
@endcomponent

Gracias,  
El equipo de {{ config('app.name') }}
@endcomponent