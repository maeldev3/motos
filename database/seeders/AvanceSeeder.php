<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Conducteur;
use App\Models\Avance;
use App\Models\AvanceRemboursement;

class AvanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conducteurs = Conducteur::where('statut', 'actif')->get();

        foreach ($conducteurs as $index => $conducteur) {
            // Une avance sur deux conducteurs actifs
            if ($index % 2 !== 0) {
                continue;
            }

            $montant = 100000 + ($index * 20000);

            $avance = Avance::create([
                'conducteur_id' => $conducteur->id,
                'type' => 'avance',
                'montant' => $montant,
                'montant_rembourse' => 0,
                'solde' => $montant,
                'date_octroi' => now()->subMonths(1),
                'commentaire' => 'Avance sur versement mensuel',
            ]);

            // Remboursement partiel logique : 2 échéances déjà versées
            $remboursementUnitaire = $montant / 4;

            for ($r = 1; $r <= 2; $r++) {
                AvanceRemboursement::create([
                    'avance_id' => $avance->id,
                    'montant' => $remboursementUnitaire,
                    'date_remboursement' => now()->subMonths(1)->addDays($r * 10),
                    'commentaire' => "Échéance {$r}/4",
                ]);
            }

            // Mise à jour du solde après remboursements
            $totalRembourse = $remboursementUnitaire * 2;
            $avance->update([
                'montant_rembourse' => $totalRembourse,
                'solde' => $montant - $totalRembourse,
            ]);
        }
    }
}
