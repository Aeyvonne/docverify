<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function store(Request $request): Response
    {
        $request->validate([
            'nom'                  => ['required', 'string', 'max:255'],
            'prenom'               => ['required', 'string', 'max:255'],
            'email'                => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'             => ['required', 'confirmed', Rules\Password::defaults()],
            'telephone'            => ['nullable', 'string', 'max:20'],
            'nom_institution'      => ['nullable', 'string', 'max:255'],
            'type_institution'     => ['nullable', 'string', 'max:255'],
            'adresse'              => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'nom'              => $request->nom,
            'prenom'           => $request->prenom,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'role'             => 'emetteur',
            'telephone'        => $request->telephone,
            'nom_institution'  => $request->nom_institution,
            'type_institution' => $request->type_institution,
            'adresse'          => $request->adresse,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}