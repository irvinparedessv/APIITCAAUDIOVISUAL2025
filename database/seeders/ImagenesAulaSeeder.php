<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aula;
use Illuminate\Support\Facades\Storage;
use App\Models\ImagenAula; // asumiendo que tienes este modelo
use App\Models\ImagenesAula;

class ImagenesAulaSeeder extends Seeder
{
    public function run()
    {
        $aulas = Aula::all();

        foreach ($aulas as $aula) {
            // Nombre de archivo basado en id
            $filename = "aula-{$aula->id}.jpg";

            // Aquí generamos o copiamos una imagen de ejemplo
            // Por simplicidad, vamos a crear un archivo vacío o copia una imagen de ejemplo

            // Ruta para almacenar en storage/app/public/aulas/
            $storagePath = "public/render_images/{$filename}";

            // Si no tienes imagen base, creamos un archivo vacío (o copia desde resources)
            if (!Storage::exists($storagePath)) {
                // Ejemplo: Crear un archivo vacío
                Storage::put($storagePath, '');

                // O si tienes una imagen base en resources:
                // $baseImage = resource_path('images/default-aula.jpg');
                // Storage::put($storagePath, file_get_contents($baseImage));
            }

            // Crear registro en imagenes_aula
            ImagenesAula::create([
                'aula_id' => $aula->id,
                // Ruta relativa accesible, sin "public/"
                'image_path' => "render_images/{$filename}",
                'is360' => in_array($aula->id, [1, 2]),
            ]);
        }
    }
}
