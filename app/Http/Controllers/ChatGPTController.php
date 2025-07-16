<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Equipo;
use App\Models\TipoReserva;
use Carbon\Carbon;

class ChatGPTController extends Controller
{
    public function chatWithGpt(Request $request)
    {
        $mensajeUsuario = $request->input('question');
        $contexto = $request->input('context') ?? [];
        $apiKey = env('OPENAI_API_KEY');

        if (!$mensajeUsuario || strlen(trim($mensajeUsuario)) < 1) {
            return response()->json(['error' => 'Mensaje vacío'], 400);
        }

        // Aquí insertamos el prompt system base al inicio del contexto (solo si no está ya)
        $promptSystemBase = <<<PROMPT
Eres un asistente virtual especializado en:

Consultar disponibilidad de espacios (aulas).

Dar recomendaciones de equipos para eventos.
No haces reservas.

Reglas estrictas:

1️⃣ Disponibilidad de aulas

Si el usuario pregunta por disponibilidad de un aula, primero confirma la fecha exacta.

Si la fecha está en lenguaje natural (ej: “12 octubre”), conviértela a DD/MM/YYYY usando el año actual 2025.

Responde solo: SIPASO2-LAFECHASOLICITADA:DD/MM/YYYY

Sin texto extra, sin explicaciones, sin frases decorativas.

2️⃣ Reservas

Si el usuario dice que quiere reservar un espacio/aula, responde solo: rEspacio

Si el usuario dice que quiere reservar un equipo, responde solo: rEquipo

3️⃣ Recomendación de equipos

Si el usuario pide recomendación de equipo, primero confirma el tipo de evento.

Si ya tienes el tipo de evento, responde solo: SIPASO:<nombre_tipo_evento>

Si no tienes el tipo de evento, pídeselo.

No confundas recomendación con reserva. Para reserva de equipo, responde rEquipo.

4️⃣ Información incompleta

Si falta fecha o tipo de evento, pide solo lo que falta.

No des información genérica, no redirijas, guía el flujo tú mismo.

5️⃣ Elección de opción

Si ya tienes toda la información necesaria y el usuario pide que elijas o recomiendes, da una respuesta clara, con una breve razón, sin usar formato SIPASO.

Ejemplo:

✅ Correcto: “La mejor opción para tu evento es el Aula Magna porque tiene mayor capacidad.”

🚫 Incorrecto: No uses SIPASO ni pidas más datos en este caso.
PROMPT;

        // Solo insertar el system prompt si no está en el contexto aún
        $hasSystemPrompt = false;
        foreach ($contexto as $msg) {
            if ($msg['role'] === 'system') {
                $hasSystemPrompt = true;
                break;
            }
        }

        if (!$hasSystemPrompt) {
            array_unshift($contexto, ['role' => 'system', 'content' => $promptSystemBase]);
        }

        // Añadir input del usuario al contexto
        $contexto[] = ['role' => 'user', 'content' => $mensajeUsuario];

        // 1️⃣ PRIMERA CONSULTA GPT
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => $contexto,
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ]);

        $respuestaGPT = trim($response->json('choices.0.message.content'));
        $contexto[] = ['role' => 'assistant', 'content' => $respuestaGPT];

        // 2️⃣ ANALIZAR respuesta de GPT, NO el mensaje del usuario
        if (preg_match('/SIPASO2-LAFECHASOLICITADA\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $respuestaGPT, $match)) {
            $fechaSolicitada = trim($match[1]);

            try {
                $fecha = Carbon::createFromFormat('d/m/Y', $fechaSolicitada);
                $fechaSolicitada = $fecha->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(['error' => 'Formato de fecha inválido.'], 400);
            }

            $aulas = Aula::obtenerAulasConBloquesPorFecha($fechaSolicitada);
            $aulasJson = $aulas->toJson(JSON_PRETTY_PRINT);

            // 3️⃣ Vuelves a GPT con los bloques
            $prompt = <<<PROMPT
El usuario necesita un espacio para la fecha {$fechaSolicitada}.
Estas son las aulas y bloques disponibles:
{$aulasJson}

Selecciona la mejor opción y explica la recomendación.
PROMPT;

            $responseFinal = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un recomendador de espacios para eventos.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            return response()->json([
                'reply' => $responseFinal->json('choices.0.message.content'),
                'context' => $contexto,
                'status' => 'SIPASO2'
            ]);
        }

        if (preg_match('/^SIPASO(.*)/', $respuestaGPT, $match)) {
            $tipoEvento = trim($match[1]);

            $equipos = Equipo::obtenerEquiposActivosConTipoReserva();



            $equiposTexto = $equipos->map(fn($eq) => "tipo {$eq['tipo_evento']}- {$eq['nombre']}: {$eq['descripcion']}")->implode("\n");

            // 3️⃣ Vuelves a GPT con los equipos
            $prompt = <<<PROMPT
El usuario tiene un evento tipo "{$tipoEvento}".
Estos son los equipos disponibles con sus tipos:
{$equiposTexto}

Selecciona los equipos más adecuados y explica la recomendación.
PROMPT;

            $responseFinal = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un recomendador de equipos para eventos.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            return response()->json([
                'reply' => $responseFinal->json('choices.0.message.content'),
                'context' => $contexto,
                'status' => 'SIPASO'
            ]);
        }

        // Si no trae SIPASO ni SIPASO2, devuelve tal cual
        return response()->json([
            'reply' => $respuestaGPT,
            'context' => $contexto,
            'status' => 'CONTINUAR'
        ]);
    }
}
