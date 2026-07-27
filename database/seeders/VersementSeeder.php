<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Affectation;
use App\Models\Versement;
class VersementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $affectationsActives = Affectation::where('active', true)->with('moto')->get();

        foreach ($affectationsActives as $affectation) {
            $moto = $affectation->moto;
            $estVoiture = $moto->type_vehicule === 'voiture';

            $periodicite = $estVoiture ? 'journalier' : 'mensuel';
            $montantAttendu = $estVoiture
                ? $moto->montant_versement_journalier
                : $moto->montant_versement_mensuel;

            // Génère les 6 dernières périodes (jours ou mois selon le type de véhicule)
            for ($i = 5; $i >= 0; $i--) {
                $date = $estVoiture ? now()->subDays($i) : now()->subMonths($i);

                // Logique de paiement : les 2 dernières périodes ont parfois du retard
                $enRetard = $i <= 1 && rand(0, 1) === 1;
                $montantVerse = $enRetard ? $montantAttendu * 0.5 : $montantAttendu;

                Versement::create([
                    'moto_id' => $moto->id,
                    'conducteur_id' => $affectation->conducteur_id,
                    'date_versement' => $date,
                    'periodicite' => $periodicite,
                    'montant_attendu' => $montantAttendu,
                    'montant_verse' => $montantVerse,
                    'reste_a_payer' => $montantAttendu - $montantVerse,
                    'en_retard' => $enRetard,
                ]);
            }
        }
    }
}
