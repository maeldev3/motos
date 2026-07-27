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
                'name' => 'Rakoto Admin',
                'email' => 'admin@motomanager.mg',
                'password' => Hash::make('Password@2026'),
                'role' => 'administrateur',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rasoa Gestionnaire',
                'email' => 'gestionnaire@motomanager.mg',
                'password' => Hash::make('Password@2026'),
                'role' => 'gestionnaire',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Randria Comptable',
                'email' => 'comptable@motomanager.mg',
                'password' => Hash::make('Password@2026'),
                'role' => 'comptable',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fara Consultation',
                'email' => 'consultation@motomanager.mg',
                'password' => Hash::make('Password@2026'),
                'role' => 'consultation',
                'actif' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}