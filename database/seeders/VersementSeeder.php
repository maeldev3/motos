<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Affectation;
use App\Models\Versement;

class VersementSeeder extends Seeder
{
    public function run(): void
    {
        // On récupère les 3 conducteurs spécifiques par leur nom pour la démo
        // (Si les IDs changent, modifiez les noms ci-dessous pour correspondre à votre DB)
        $njakaAffectation = Affectation::where('active', true)->whereHas('conducteur', function($q) {
            $q->where('nom', 'like', '%Njaka%');
        })->with('moto', 'conducteur')->first();

        $jeseAffectation = Affectation::where('active', true)->whereHas('conducteur', function($q) {
            $q->where('nom', 'like', '%Jese%');
        })->with('moto', 'conducteur')->first();

        $fihorenanaAffectation = Affectation::where('active', true)->whereHas('conducteur', function($q) {
            $q->where('nom', 'like', '%Fihorenana%');
        })->with('moto', 'conducteur')->first();

        // 1. VERSEMENTS DE NJAKA (Moto 1) - 6 mois d'historique
        if ($njakaAffectation) {
            $moto = $njakaAffectation->moto;
            $conducteur = $njakaAffectation->conducteur;
            $montantAttendu = $moto->montant_versement_mensuel ?? 600000;

            $datesNjaka = [
                ['date' => '2026-02-28', 'montant' => 360000], // 28 Février : 360000ar
                ['date' => '2026-03-26', 'montant' => 600000], // 26 Mars : 600000ar
                ['date' => '2026-04-30', 'montant' => 600000], // 30 Avril : 600000 ar
                ['date' => '2026-05-30', 'montant' => 500000], // 30 Mai : 500000ar
                ['date' => '2026-06-30', 'montant' => 300000], // 30 juin : 300000ar
                ['date' => '2026-07-30', 'montant' => 400000], // 30 juillet : 400000ar
            ];

            foreach ($datesNjaka as $data) {
                $date = \Carbon\Carbon::parse($data['date']);
                $montantVerse = $data['montant'];
                $enRetard = $montantVerse < $montantAttendu;

                Versement::create([
                    'moto_id' => $moto->id,
                    'conducteur_id' => $conducteur->id,
                    'date_versement' => $date,
                    'periodicite' => 'mensuel',
                    'montant_attendu' => $montantAttendu,
                    'montant_verse' => $montantVerse,
                    'reste_a_payer' => max(0, $montantAttendu - $montantVerse),
                    'en_retard' => $enRetard,
                    'commentaire' => 'Versement mensuel ' . $date->format('F Y'),
                ]);
            }
        }

        // 2. VERSEMENTS DE JESE (Moto 2) - 2 mois d'historique (Image 2)
        if ($jeseAffectation) {
            $moto = $jeseAffectation->moto;
            $conducteur = $jeseAffectation->conducteur;
            $montantAttendu = $moto->montant_versement_mensuel ?? 600000;

            // Dates exactes de votre capture d'écran
            $datesJese = [
                ['date' => '2026-06-30', 'montant' => 600000], // Mois juin : 600000ar (30 juillet)
                ['date' => '2026-07-30', 'montant' => 600000], // Mois Juillet : 600000ar 30 juillet
            ];

            foreach ($datesJese as $data) {
                $date = \Carbon\Carbon::parse($data['date']);
                $montantVerse = $data['montant'];
                $enRetard = false;

                Versement::create([
                    'moto_id' => $moto->id,
                    'conducteur_id' => $conducteur->id,
                    'date_versement' => $date,
                    'periodicite' => 'mensuel',
                    'montant_attendu' => $montantAttendu,
                    'montant_verse' => $montantVerse,
                    'reste_a_payer' => 0,
                    'en_retard' => $enRetard,
                    'commentaire' => 'Versement mensuel ' . $date->format('F Y'),
                ]);
            }
        }

        // 3. VERSEMENTS DE FIHORENANA (Moto 3) - 4 mois d'historique (Image 3)
        if ($fihorenanaAffectation) {
            $moto = $fihorenanaAffectation->moto;
            $conducteur = $fihorenanaAffectation->conducteur;
            $montantAttendu = $moto->montant_versement_mensuel ?? 600000;

            // Dates exactes de votre capture d'écran
            $datesFihorenana = [
                ['date' => '2026-04-30', 'montant' => 400000], // 30 Avril 400000ar
                ['date' => '2026-05-30', 'montant' => 300000], // 30 Mai 300000ar
                ['date' => '2026-06-30', 'montant' => 600000], // 30 Juin 600000ar
                ['date' => '2026-07-30', 'montant' => 600000], // 30 Juillet 600000ar
            ];

            foreach ($datesFihorenana as $data) {
                $date = \Carbon\Carbon::parse($data['date']);
                $montantVerse = $data['montant'];
                $enRetard = $montantVerse < $montantAttendu;

                Versement::create([
                    'moto_id' => $moto->id,
                    'conducteur_id' => $conducteur->id,
                    'date_versement' => $date,
                    'periodicite' => 'mensuel',
                    'montant_attendu' => $montantAttendu,
                    'montant_verse' => $montantVerse,
                    'reste_a_payer' => max(0, $montantAttendu - $montantVerse),
                    'en_retard' => $enRetard,
                    'commentaire' => 'Versement mensuel ' . $date->format('F Y'),
                ]);
            }
        }
    }
}