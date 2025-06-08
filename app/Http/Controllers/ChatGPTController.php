<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatGPTController extends Controller
{
    public function chatWithGpt(Request $request)
    {
        try {
            $prompt = $request->input('question');
            $apiKey = env('OPENAI_API_KEY');
            $assistantId = env('OPENAI_ASSISTANT_ID');
            if (!$prompt) {
                return response()->json(['error' => 'No se proporcionó el prompt o pregunta.'], 400);
            }
            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'OpenAI-Beta' => 'assistants=v2',
                'Content-Type' => 'application/json',
            ];

            // 1. Crear thread
            $threadResponse = Http::withHeaders($headers)
                ->post('https://api.openai.com/v1/threads', []);
            if (!$threadResponse->ok()) {
                throw new \Exception('Error creando thread: ' . $threadResponse->body());
            }
            $threadId = $threadResponse->json('id');

            // 2. Agregar mensaje
            $messageResponse = Http::withHeaders($headers)
                ->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                    'role' => 'user',
                    'content' => $prompt,
                ]);
            if (!$messageResponse->ok()) {
                throw new \Exception('Error agregando mensaje: ' . $messageResponse->body());
            }

            // 3. Ejecutar run
            $runResponse = Http::withHeaders($headers)
                ->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
                    'assistant_id' => $assistantId,
                ]);
            if (!$runResponse->ok()) {
                throw new \Exception('Error creando run: ' . $runResponse->body());
            }
            $runId = $runResponse->json('id');

            // 4. Esperar el run
            do {
                sleep(1);
                $runStatus = Http::withHeaders($headers)
                    ->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");
                $status = $runStatus->json('status');
            } while ($status !== 'Completado' && $status !== 'failed');

            if ($status === 'failed') {
                throw new \Exception('El assistant falló al generar la respuesta.');
            }

            // 5. Obtener mensaje
            $messagesResponse = Http::withHeaders($headers)
                ->get("https://api.openai.com/v1/threads/{$threadId}/messages");
            if (!$messagesResponse->ok()) {
                throw new \Exception('Error obteniendo mensajes: ' . $messagesResponse->body());
            }

            $messages = $messagesResponse->json('data');
            $assistantReplies = collect($messages)
                ->where('role', 'assistant')
                ->map(function ($msg) {
                    return $msg['content'][0]['text']['value'] ?? null;
                })
                ->filter() // Elimina nulls si hay
                ->values(); // Reindexa el array
            $lastReply = $assistantReplies->last();

            return response()->json(['reply' => $lastReply]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
