<?php

namespace Database\Seeders;

use App\Models\Moto;
use Illuminate\Database\Seeder;

class MotoSeeder extends Seeder
{
    public function run(): void
    {
        $motos = [
            ['immatriculation' => '0001 AAA', 'conducteur' => 'Njaka', 'marque' => 'PETER', 'modele' => '23', 'couleur' => 'Blanche'],
            ['immatriculation' => '0002 BBB', 'conducteur' => 'Jese', 'marque' => 'PETER', 'modele' => '7', 'couleur' => 'Noire'],
            ['immatriculation' => '0003 CCC', 'conducteur' => 'Fihorenana', 'marque' => 'PETER', 'modele' => '7', 'couleur' => 'Noire'],
            ['immatriculation' => '0004 DDD', 'conducteur' => 'Manana', 'marque' => 'PETER', 'modele' => '7', 'couleur' => 'Rouge'],
        ];

        foreach ($motos as $i => $data) {
            Moto::updateOrCreate(
                ['immatriculation' => $data['immatriculation']],
                [
                    'marque' => $data['marque'],
                    'modele' => $data['modele'],
                    'couleur' => $data['couleur'],
                    'type_vehicule' => 'moto',
                    'statut' => 'en_circulation',
                    'annee_fabrication' => 2023,
                    'numero_chassis' => 'CH' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                    'numero_moteur' => 'MT' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                    'date_achat' => now()->subYear(),
                    'prix_achat' => 6400000,
                    'montant_versement_mensuel' => 600000,
                    'montant_versement_journalier' => 0,
                    'montant_versement_hebdomadaire' => $data['conducteur'] === 'Manana' ? 170000 : 0,
                    'periodicite_versement' => $data['conducteur'] === 'Manana' ? 'hebdomadaire' : 'mensuel',
                    'actif' => true,
                ]
            );
        }
    }
}
