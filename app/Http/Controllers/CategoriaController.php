<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Listar todas las categorías activas (no eliminadas).
     */
    public function index()
    {
        $categorias = Categoria::where('is_deleted', false)->get();


        return response()->json($categorias);
    }
}
