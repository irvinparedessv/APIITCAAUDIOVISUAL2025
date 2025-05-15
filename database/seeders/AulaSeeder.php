<?php

namespace Database\Seeders;

use App\Models\Aula;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AulaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $nombres = ['Aula 101', 'Aula 202', 'Auditorio', 'Sala de grabación'];

        foreach ($nombres as $nombre) {
            Aula::firstOrCreate(['name' => $nombre]);
        }
    }
}
