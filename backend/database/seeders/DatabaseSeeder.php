<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Compte administrateur (créé uniquement en base, jamais via l'inscription)
        User::firstOrCreate(
            ['email' => 'admin@docverify.sn'],
            [
                'nom'             => 'Admin',
                'prenom'          => 'DocVerify',
                'password'        => Hash::make('Admin@1234'),
                'role'            => 'admin',
                'is_active'       => true,
                'is_certified'    => false,
                'nom_institution' => 'DocVerify Platform',
            ]
        );

        // Émetteur standard de test
        User::firstOrCreate(
            ['email' => 'emetteur@test.sn'],
            [
                'nom'             => 'Diallo',
                'prenom'          => 'Moussa',
                'password'        => Hash::make('Emetteur@1234'),
                'role'            => 'emetteur',
                'is_active'       => true,
                'is_certified'    => false,
                'nom_institution' => 'Université Cheikh Anta Diop',
                'type_institution'=> 'université',
                'telephone'       => '+221771234567',
            ]
        );
    }
}
