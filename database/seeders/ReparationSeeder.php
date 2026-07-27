<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Reparation;

class ReparationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $motosAReparer = Moto::whereIn('statut', ['en_reparation', 'accidentee', 'en_entretien'])->get();

        $mapping = [
            'en_reparation' => ['type' => 'moteur', 'montant' => 350000],
            'accidentee' => ['type' => 'accident', 'montant' => 1200000],
            'en_entretien' => ['type' => 'vidange', 'montant' => 45000],
        ];

        foreach ($motosAReparer as $moto) {
            $info = $mapping[$moto->statut];

            Reparation::create([
                'moto_id' => $moto->id,
                'date_reparation' => now()->subDays(rand(1, 15)),
                'type_reparation' => $info['type'],
                'description' => "Intervention suite à statut '{$moto->statut}'",
                'garage' => 'Garage Andravoahangy',
                'mecanicien' => 'Rakoto Mécanicien',
                'kilometrage' => rand(15000, 60000),
                'montant' => $info['montant'],
                'observations' => 'Suivi en cours',
            ]);
        }

        // Historique : quelques motos en circulation ont eu une vidange normale récente
        $motosEnCirculation = Moto::where('statut', 'en_circulation')->take(3)->get();

        foreach ($motosEnCirculation as $moto) {
            Reparation::create([
                'moto_id' => $moto->id,
                'date_reparation' => now()->subMonths(1),
                'type_reparation' => 'vidange',
                'description' => 'Vidange périodique',
                'garage' => 'Garage Behoririka',
                'kilometrage' => rand(10000, 40000),
                'montant' => 40000,
            ]);
        }
    }
}
