<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ReservaAula;
use Illuminate\Support\Facades\Log;

class ModelUploadController extends Controller
{
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file provided'], 400);
        }

        $file = $request->file('file');

        if (!$file->isValid()) {
            return response()->json(['error' => 'Uploaded file is not valid'], 400);
        }

        $extension = $file->getClientOriginalExtension();
        Log::info('EXTENSION DETECTADA:', [$extension]);
        Log::info('MIME TYPE:', [$file->getMimeType()]);
        if (!$extension) {
            // intenta deducir por MIME
            $mime = $file->getMimeType();      // model/gltf-binary
            $extension = $mime === 'model/gltf-binary' ? 'glb' : '';
        }

        if (!in_array(strtolower($extension), ['glb', 'gltf'])) {
            return response()->json(['error' => 'Unsupported file type'], 422);
        }

        $randomName = Str::uuid() . '.' . $extension;

        try {
            $path = $file->storeAs("models", $randomName);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to store file', 'exception' => $e->getMessage()], 500);
        }

        if (!$path) {
            return response()->json(['error' => 'Storage returned empty path'], 500);
        }

        $reserveId = $request->input('reserveId');
        if (!$reserveId) {
            return response()->json(['error' => 'Missing reserveId'], 400);
        }

        $reserva = ReservaAula::find($reserveId);
        if (!$reserva) {
            return response()->json(['error' => 'Reserva not found'], 404);
        }

        try {
            // Eliminar modelo anterior si existe
            if ($reserva->path_model && Storage::exists($reserva->path_model)) {
                Storage::delete($reserva->path_model);
            }

            $reserva->path_model = $path;
            $reserva->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update reserva', 'exception' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'path' => $path,
            'filename' => $randomName,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    public function getModelPath($id)
    {
        $reserva = ReservaAula::find($id);

        if (!$reserva || !$reserva->path_model) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        // Retorna ruta accesible vía storage link
        return response()->json([
            'path' => asset('api/' . $reserva->path_model)
        ]);
    }
}
