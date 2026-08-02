<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::insert([
            [
                'name' => 'Rabemiafara',
                'email' => 'ismael',
                'password' => Hash::make('password'),
                'role' => 'administrateur',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dev Gestionnaire',
                'email' => 'maeldev3@motomanager.com',
                'password' => Hash::make('password'),
                'role' => 'gestionnaire',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jese Comptable',
                'email' => 'jese@motomanager.mg',
                'password' => Hash::make('password'),
                'role' => 'comptable',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fara Consultation',
                'email' => 'njaka@motomanager.mg',
                'password' => Hash::make('password'),
                'role' => 'consultation',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}