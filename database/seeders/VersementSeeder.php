<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\Versement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VersementSeeder extends Seeder
{
    public function run(): void
    {
        $historique = [
            // Njaka : versements mensuels. Les mois avec absence ont un attendu réduit.
            ['conducteur' => 'Njaka', 'date' => '2026-02-28', 'montant' => 360000, 'attendu' => 360000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-03-26', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-04-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-05-30', 'montant' => 500000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-06-30', 'montant' => 300000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-07-30', 'montant' => 400000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-08-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-09-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Njaka', 'date' => '2026-10-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],


            // Fihorenana (Beloha)
            ['conducteur' => 'Fihorenana', 'date' => '2026-04-30', 'montant' => 400000, 'attendu' => 400000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Fihorenana', 'date' => '2026-05-30', 'montant' => 300000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Fihorenana', 'date' => '2026-06-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Fihorenana', 'date' => '2026-07-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Fihorenana', 'date' => '2026-08-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Fihorenana', 'date' => '2026-09-30', 'montant' => 300000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Fihorenana', 'date' => '2026-10-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],

            // Jese
            ['conducteur' => 'Jese', 'date' => '2026-06-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Jese', 'date' => '2026-07-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Jese', 'date' => '2026-08-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Jese', 'date' => '2026-09-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],
            ['conducteur' => 'Jese', 'date' => '2026-10-30', 'montant' => 600000, 'attendu' => 600000, 'periodicite' => 'mensuel'],


            // La note affiche 6 000 000 Ar en octobre ; 600 000 Ar est retenu provisoirement.

            // Manana : paiements hebdomadaires à 170 000 Ar. Le 20/10 est un paiement partiel.
            ['conducteur' => 'Manana', 'date' => '2026-10-18', 'montant' => 170000, 'attendu' => 170000, 'periodicite' => 'hebdomadaire'],
            ['conducteur' => 'Manana', 'date' => '2026-10-20', 'montant' => 85000, 'attendu' => 85000, 'periodicite' => 'hebdomadaire'],
            ['conducteur' => 'Manana', 'date' => '2026-10-27', 'montant' => 170000, 'attendu' => 170000, 'periodicite' => 'hebdomadaire'],
            ['conducteur' => 'Manana', 'date' => '2026-11-07', 'montant' => 170000, 'attendu' => 170000, 'periodicite' => 'hebdomadaire'],
        ];

        foreach ($historique as $item) {
            $affectation = Affectation::with('moto', 'conducteur')
                ->where('active', true)
                ->whereHas('conducteur', fn ($q) => $q->where('nom', $item['conducteur']))
                ->first();

            if (!$affectation) {
                continue;
            }

            $date = Carbon::parse($item['date']);
            $montant = $item['montant'];
            $attendu = $item['attendu'];

            Versement::updateOrCreate(
                [
                    'moto_id' => $affectation->moto_id,
                    'conducteur_id' => $affectation->conducteur_id,
                    'date_versement' => $date->toDateString(),
                ],
                [
                    'commentaire' => 'Historique ' . $item['periodicite'] . ' ' . $date->format('Y-m-d'),
                    'periodicite' => $item['periodicite'],
                    'montant_attendu' => $attendu,
                    'montant_verse' => $montant,
                    'reste_a_payer' => max(0, $attendu - $montant),
                    'en_retard' => max(0, $attendu - $montant) > 0 && $date->lt(now()),
                ]
            );
        }
    }
}
