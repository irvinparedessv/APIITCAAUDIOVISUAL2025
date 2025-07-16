<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Aula;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuariosEncargadosSeeder extends Seeder
{
    public function run()
    {
        $aulas = Aula::all();

        foreach ($aulas as $aula) {
            // Crear usuario con índice
            $user = User::create([
                'first_name'        => 'EncargadoEspacio' . $aula->id,
                'last_name'         => 'EspacioLast' . $aula->id,
                'email'             => 'espacio' . $aula->id . '@yopmail.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('123'),
                'estado'            => 1,
                'is_deleted'        => false,
                'remember_token'    => null,
                'role_id'           => 4,
                'phone'             => null,
                'address'           => null,
                'image'             => null,
            ]);

            // Insertar relación en tabla pivot aula_user
            DB::table('aula_user')->insert([
                'aula_id' => $aula->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
