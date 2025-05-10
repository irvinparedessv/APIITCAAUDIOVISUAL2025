@component('mail::message')
# Confirmación de Cuenta

Hola {{ $user->first_name }},

@component('mail::button', [
    'url' => $confirmationUrl,
    'color' => 'primary'
])
    Confirmar Cuenta (Token Completo: {{ $user->confirmation_token }})
@endcomponent

{{-- Versión alternativa como enlace de texto --}}
<p>O copia este enlace manualmente:</p>
<p style="word-break: break-all;">
    <a href="{{ $confirmationUrl }}">{{ $confirmationUrl }}</a>
</p>

@endcomponent