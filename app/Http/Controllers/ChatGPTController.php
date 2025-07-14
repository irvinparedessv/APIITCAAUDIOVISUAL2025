<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ChatGPTController extends Controller
{
    public function chatWithGpt(Request $request)
    {
        try {
            $prompt = $request->input('question');
            Log::info('Prompt recibido', ['prompt' => $prompt]);

            $apiKey = env('OPENAI_API_KEY');
            $assistantId = env('OPENAI_ASSISTANT_ID');
            Log::info('API Key y Assistant ID cargados', [
                'apiKey' => substr($apiKey, 0, 8) . '...',
                'assistantId' => $assistantId,
            ]);

            if (!$prompt || strlen(trim($prompt)) < 3) {
                Log::warning('Prompt inválido');
                return response()->json(['error' => 'Prompt inválido o demasiado corto.'], 400);
            }

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'OpenAI-Beta' => 'assistants=v2',
                'Content-Type' => 'application/json',
            ];

            // 1. Crear thread
            $threadResponse = Http::withHeaders($headers)->post('https://api.openai.com/v1/threads', []);
            Log::info('Thread creado', ['response' => $threadResponse->body()]);
            if (!$threadResponse->ok()) {
                Log::error('Error creando thread', ['body' => $threadResponse->body()]);
                return response()->json(['error' => 'Error creando thread: ' . $threadResponse->body()], 500);
            }
            $threadId = $threadResponse->json('id');
            Log::info('Thread ID', ['threadId' => $threadId]);

            // 2. Agregar mensaje
            $messageResponse = Http::withHeaders($headers)->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                'role' => 'user',
                'content' => $prompt,
            ]);
            Log::info('Mensaje agregado', ['response' => $messageResponse->body()]);
            if (!$messageResponse->ok()) {
                Log::error('Error agregando mensaje', ['body' => $messageResponse->body()]);
                return response()->json(['error' => 'Error agregando mensaje: ' . $messageResponse->body()], 500);
            }

            // 3. Ejecutar run
            $runResponse = Http::withHeaders($headers)->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
                'assistant_id' => $assistantId,
            ]);
            Log::info('Run creado', ['response' => $runResponse->body()]);
            if (!$runResponse->ok()) {
                Log::error('Error creando run', ['body' => $runResponse->body()]);
                return response()->json(['error' => 'Error creando run: ' . $runResponse->body()], 500);
            }
            $runId = $runResponse->json('id');
            Log::info('Run ID', ['runId' => $runId]);

            // 4. Polling limitado
            $maxRetries = 10;
            $retryCount = 0;
            $status = '';

            do {
                sleep(5);
                $runStatus = Http::withHeaders($headers)->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");
                Log::info('Chequeando estado', ['response' => $runStatus->body()]);
                if (!$runStatus->ok()) {
                    Log::error('Error obteniendo estado del run', ['body' => $runStatus->body()]);
                    return response()->json(['error' => 'Error obteniendo estado del run: ' . $runStatus->body()], 500);
                }
                $status = $runStatus->json('status');
                Log::info('Estado actual', ['status' => $status, 'retry' => $retryCount]);
                $retryCount++;
            } while ($status !== 'completed' && $status !== 'failed' && $retryCount < $maxRetries);

            if ($status === 'failed') {
                Log::error('Run fallido');
                return response()->json(['error' => 'El assistant falló al generar la respuesta.'], 500);
            }

            if ($status !== 'completed') {
                Log::error('Timeout esperando respuesta');
                return response()->json(['error' => 'Tiempo de espera agotado, sin respuesta completa.'], 504);
            }

            // 5. Obtener mensajes
            $messagesResponse = Http::withHeaders($headers)->get("https://api.openai.com/v1/threads/{$threadId}/messages");
            Log::info('Mensajes obtenidos', ['response' => $messagesResponse->body()]);
            if (!$messagesResponse->ok()) {
                Log::error('Error obteniendo mensajes', ['body' => $messagesResponse->body()]);
                return response()->json(['error' => 'Error obteniendo mensajes: ' . $messagesResponse->body()], 500);
            }

            $messages = $messagesResponse->json('data');
            $assistantReplies = collect($messages)
                ->where('role', 'assistant')
                ->map(fn($msg) => $msg['content'][0]['text']['value'] ?? null)
                ->filter()
                ->values();

            $lastReply = $assistantReplies->last();
            Log::info('Respuesta final', ['reply' => $lastReply]);

            return response()->json(['reply' => $lastReply]);
        } catch (\Exception $e) {
            Log::error('Excepción capturada', ['exception' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
