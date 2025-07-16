<?php

//JOSUE (REQUEST CREADO)
//Request creado para cuando se actualice usuario, solo permita correo institucional

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permitir que se use esta request
    }

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',

            // Validamos que el email sea único excepto para el mismo usuario
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $this->route('user'), // Excluye el email del usuario actual
            ],

            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1,3',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            // No se necesita mensaje personalizado si no hay validación especial
        ];
    }
}
