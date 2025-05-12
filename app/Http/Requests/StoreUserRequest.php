<?php

// JOSUE (REQUEST CREADO)
//Se creo este Request para validar que se permita solo correo institucionales. 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permite ejecutar este request
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
             // ✅ Validamos que el email sea único y de dominio @itca.edu.sv
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@itca\.edu\.sv$/'
            ],
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1,3',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            // ✅ Mensaje personalizado para correos no institucionales
            'email.regex' => 'Solo se permiten correos institucionales @itca.edu.sv.',
        ];
    }
}
