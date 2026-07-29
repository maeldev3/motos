<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Depense;
use Carbon\Carbon;

class DepenseSeeder extends Seeder
{
    public function run(): void
    {
        // CORRECTION IMPORTANTE : On ne prend que les motos qui roulent (en_circulation ou disponible)
        // Une moto accidentée ou en réparation ne consomme pas de carburant et ne va pas au lavage.
        $motos = Moto::whereIn('statut', ['en_circulation', 'disponible'])->get();

        foreach ($motos as $moto) {
            $type = $moto->type_vehicule;
            $montantCarburant = $type === 'voiture' ? 80000 : 15000;

            // =======================================================
            // 1. DÉPENSES RÉCURRENTES (Hebdomadaires) sur 6 mois
            // =======================================================
            for ($i = 0; $i < 24; $i++) {
                $date = Carbon::now()->subWeeks(rand(1, 24))->startOfWeek()->addDays(rand(0, 6));

                $categorie = match (rand(1, 10)) {
                    1, 2, 3, 4, 5, 6 => 'carburant',
                    7, 8 => 'lavage',
                    default => 'parking',
                };

                $montant = match ($categorie) {
                    'carburant' => $montantCarburant + rand(0, 5000),
                    'lavage' => 3000,
                    'parking' => 2000,
                };

                Depense::create([
                    'moto_id' => $moto->id,
                    'date_depense' => $date,
                    'categorie' => $categorie,
                    'montant' => $montant,
                    'commentaire' => ucfirst($categorie) . ' - ' . $moto->immatriculation,
                ]);
            }

            // =======================================================
            // 2. RENFORT CADRE (150 000 Ar) - TOUS LES MOIS
            // =======================================================
            for ($m = 0; $m < 6; $m++) {
                $date = Carbon::now()->subMonths($m)->startOfMonth()->addDays(rand(5, 15));

                Depense::create([
                    'moto_id' => $moto->id,
                    'date_depense' => $date,
                    'categorie' => 'renfort_cadre',
                    'montant' => 150000,
                    'commentaire' => 'Renfort cadre mensuel - ' . $date->format('F Y'),
                ]);
            }

            // =======================================================
            // 3. ASSURANCE ANNUELLE
            // =======================================================
            $montantAssurance = $type === 'voiture' ? 450000 : 120000;

            Depense::create([
                'moto_id' => $moto->id,
                'date_depense' => Carbon::now()->subMonths(2),
                'categorie' => 'assurance',
                'montant' => $montantAssurance,
                'commentaire' => 'Assurance annuelle (renouvellement récent)',
            ]);

            if (rand(0, 1) === 1) {
                Depense::create([
                    'moto_id' => $moto->id,
                    'date_depense' => Carbon::now()->subMonths(14),
                    'categorie' => 'assurance',
                    'montant' => $montantAssurance,
                    'commentaire' => 'Assurance annuelle (exercice précédent)',
                ]);
            }
        }
    }
}