<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PruebaMail;

class EmailController extends Controller
{
    public function enviarCorreo()
    {
        $destinatario = 'destino@example.com';
        $contenido = 'Este es el contenido dinámico del correo enviado desde EmailController';

        Mail::to($destinatario)->send(new PruebaMail($contenido));

        return response()->json(['mensaje' => 'Correo enviado correctamente']);
    }
}
