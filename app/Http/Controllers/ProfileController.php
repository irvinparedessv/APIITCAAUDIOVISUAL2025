<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('role');

        $userArray = $user->toArray();
        $userArray['image_url'] = $user->image_url;  // Agregamos URL completa

        return response()->json([
            'user' => $userArray,
        ]);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $image = $request->file('image');
            $path = $image->store('user_images', 'public');
            $user->image = $path;
        }

        $user->first_name = $request->input('first_name', $user->first_name);
        $user->last_name = $request->input('last_name', $user->last_name);
        $user->phone = $request->input('phone', $user->phone);
        $user->address = $request->input('address', $user->address);

        $user->save();
        $user->refresh();

        $userArray = $user->toArray();
        $userArray['image_url'] = $user->image_url;

        return response()->json([
            'message' => 'Perfil actualizado con éxito',
            'user' => $userArray,
        ]);
    }
}
