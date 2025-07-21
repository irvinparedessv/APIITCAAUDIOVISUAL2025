<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ReservaAula;

class ModelUploadController extends Controller
{
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file provided'], 400);
        }

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $path = $file->storeAs("models", $filename);

        $reserveId = $request->input('reserveId');
        if ($reserveId) {
            $reserva = ReservaAula::find($reserveId);
            if ($reserva) {
                $reserva->path_model = $path;
                $reserva->save();
            }
        }

        return response()->json(['success' => true, 'path' => $path]);
    }
}
