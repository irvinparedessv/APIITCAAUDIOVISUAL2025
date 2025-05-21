<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aula;
use App\Models\ImagenesAula;
use App\Models\HorarioAulas;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;


class ChatGPTController extends Controller
{

    public function chatWithGpt(Request $request)
    {
        $prompt = $request->input('prompt'); // Recibe la pregunta desde el frontend

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo', // o 'gpt-4' si tienes acceso
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ]
            ],
            'temperature' => 0.7,
        ]);

        return response()->json($response->json());
    }
}
