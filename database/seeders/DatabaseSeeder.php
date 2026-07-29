<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'ismael@moto.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password123'),
                'role' => 'administrateur',
                'actif' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'gestionnaire@moto-api.com'],
            [
                'name' => 'Gestionnaire',
                'password' => Hash::make('password123'),
                'role' => 'gestionnaire',
                'actif' => true,
            ]
        );

        $this->call([
            UserSeeder::class,
            MotoSeeder::class,
            ConducteurSeeder::class,
            AffectationSeeder::class,   // dépend de motos + conducteurs
            VersementSeeder::class,     // dépend de affectations
            AbsenceSeeder::class,       // dépend de conducteurs
            AvanceSeeder::class,        // dépend de conducteurs
            ReparationSeeder::class,    // dépend de motos
            DepenseSeeder::class,       // dépend de motos
            AlerteSeeder::class,        // dépend de motos + versements + conducteurs
        ]);
    }
}
