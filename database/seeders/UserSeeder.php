<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // REVENDEUR
        User::create([
            'name' => 'Revendeur Démo',
            'email' => 'revendeur@example.com',
            'password' => Hash::make('password'),
            'role' => 'revendeur',
        ]);
    }
}

