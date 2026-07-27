<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Moto;

class MotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $motos = [
            ['immatriculation' => '1234 TBA', 'marque' => 'Yamaha', 'modele' => 'Sirius', 'couleur' => 'Rouge', 'type_vehicule' => 'moto', 'statut' => 'en_circulation'],
            ['immatriculation' => '1235 TBA', 'marque' => 'Honda', 'modele' => 'Wave 110', 'couleur' => 'Noir', 'type_vehicule' => 'moto', 'statut' => 'en_circulation'],
            ['immatriculation' => '1236 TBA', 'marque' => 'Suzuki', 'modele' => 'Smash', 'couleur' => 'Bleu', 'type_vehicule' => 'moto', 'statut' => 'en_reparation'],
            ['immatriculation' => '1237 TBA', 'marque' => 'Yamaha', 'modele' => 'Sirius', 'couleur' => 'Blanc', 'type_vehicule' => 'moto', 'statut' => 'en_circulation'],
            ['immatriculation' => '1238 TBA', 'marque' => 'TVS', 'modele' => 'Star City', 'couleur' => 'Rouge', 'type_vehicule' => 'moto', 'statut' => 'disponible'],
            ['immatriculation' => '1239 TBA', 'marque' => 'Honda', 'modele' => 'Wave 125', 'couleur' => 'Gris', 'type_vehicule' => 'moto', 'statut' => 'en_entretien'],
            ['immatriculation' => '1240 TBA', 'marque' => 'Bajaj', 'modele' => 'Boxer', 'couleur' => 'Noir', 'type_vehicule' => 'moto', 'statut' => 'accidentee'],
            ['immatriculation' => '1241 TBA', 'marque' => 'Yamaha', 'modele' => 'Crux', 'couleur' => 'Bleu', 'type_vehicule' => 'moto', 'statut' => 'en_circulation'],
            ['immatriculation' => '7001 TBB', 'marque' => 'Toyota', 'modele' => 'Hiace', 'couleur' => 'Blanc', 'type_vehicule' => 'voiture', 'statut' => 'en_circulation'],
            ['immatriculation' => '7002 TBB', 'marque' => 'Suzuki', 'modele' => 'Alto', 'couleur' => 'Argent', 'type_vehicule' => 'voiture', 'statut' => 'hors_service'],
        ];

        foreach ($motos as $i => $data) {
            Moto::create(array_merge($data, [
                'annee_fabrication' => rand(2016, 2023),
                'numero_chassis' => 'CH' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                'numero_moteur' => 'MT' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                'date_achat' => now()->subMonths(rand(3, 30)),
                'prix_achat' => $data['type_vehicule'] === 'voiture' ? rand(15000000, 35000000) : rand(2500000, 5500000),
                'montant_versement_mensuel' => $data['type_vehicule'] === 'voiture' ? 0 : 600000,
                'montant_versement_journalier' => $data['type_vehicule'] === 'voiture' ? 100000 : 0,
                'actif' => $data['statut'] !== 'vendue',
            ]));
        }
    }
}
