<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Reparation;
use App\Models\Affectation;
use Carbon\Carbon;

class ReparationSeeder extends Seeder
{
    public function run(): void
    {
        // ================================================================
        // 1. RÉPARATION SPÉCIALE : SOUDURE CADRE (Moto de Fihorenana)
        // ================================================================
        $affectationFihorenana = Affectation::where('active', true)
            ->whereHas('conducteur', function ($q) {
                $q->where('nom', 'like', '%Fihorenana%');
            })
            ->with('moto')
            ->first();

        if ($affectationFihorenana) {
            $motoFihorenana = $affectationFihorenana->moto;

            Reparation::create([
                'moto_id' => $motoFihorenana->id,
                'date_reparation' => Carbon::now()->subDays(rand(3, 10)),
                'type_reparation' => 'renfort_cadre',
                'description' => 'Réparation de soudure sur le cadre de la moto',
                'garage' => 'Garage Andravoahangy',
                'mecanicien' => 'Rakoto Mécanicien',
                'kilometrage' => rand(15000, 30000),
                'montant' => 100000,
                'observations' => 'Soudure renforcée, châssis consolidé',
            ]);
        }

        // ================================================================
        // 2. AUTRES RÉPARATIONS (Motos en panne)
        // ================================================================
        $motosAReparer = Moto::whereIn('statut', ['en_reparation', 'accidentee', 'en_entretien'])->get();

        $mapping = [
            'en_reparation' => ['type' => 'moteur', 'montant' => 350000],
            'accidentee' => ['type' => 'accident', 'montant' => 1200000],
            'en_entretien' => ['type' => 'vidange', 'montant' => 45000],
        ];

        foreach ($motosAReparer as $moto) {
            if (isset($motoFihorenana) && $moto->id === $motoFihorenana->id) {
                continue; 
            }

            $info = $mapping[$moto->statut] ?? ['type' => 'autres', 'montant' => 50000];

            Reparation::create([
                'moto_id' => $moto->id,
                'date_reparation' => Carbon::now()->subDays(rand(1, 15)),
                'type_reparation' => $info['type'],
                'description' => "Intervention suite à statut '{$moto->statut}'",
                'garage' => 'Garage Andravoahangy',
                'mecanicien' => 'Rakoto Mécanicien',
                'kilometrage' => rand(15000, 60000),
                'montant' => $info['montant'],
                'observations' => 'Suivi en cours',
            ]);
        }

        // ================================================================
        // 3. HISTORIQUE : VIDANGES RÉCENTES
        // ================================================================
        $idsToExclude = [];
        if (isset($motoFihorenana)) {
            $idsToExclude[] = $motoFihorenana->id;
        }

        $motosEnCirculation = Moto::where('statut', 'en_circulation')
            ->whereNotIn('id', $idsToExclude)
            ->take(3)
            ->get();

        foreach ($motosEnCirculation as $moto) {
            Reparation::create([
                'moto_id' => $moto->id,
                'date_reparation' => Carbon::now()->subMonths(1),
                'type_reparation' => 'vidange',
                'description' => 'Vidange périodique',
                'garage' => 'Garage Behoririka',
                'kilometrage' => rand(10000, 40000),
                'montant' => 25000, // <-- Montant ajusté (plus réaliste pour une vidange)
                'observations' => 'Huile moteur changée',
            ]);
        }
    }
}