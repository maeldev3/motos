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
            // ==========================================
            // 3 MOTOS EN CIRCULATION (pour les 3 conducteurs)
            // ==========================================
            ['immatriculation' => '1234 TBA', 'marque' => 'PETER 7', 'modele' => '150GY', 'couleur' => 'Rouge',   'type_vehicule' => 'moto', 'statut' => 'en_circulation'],
            ['immatriculation' => '1235 TBA', 'marque' => 'PETER 7',  'modele' => '150GY', 'couleur' => 'Noir',    'type_vehicule' => 'moto', 'statut' => 'en_circulation'],
            ['immatriculation' => '1236 TBA', 'marque' => 'PETER 7', 'modele' => '150GY 200cc',   'couleur' => 'Noir',    'type_vehicule' => 'moto', 'statut' => 'en_circulation'],

            // ==========================================
            // 4 AUTRES MOTOS (Indisponibles ou en attente)
            // ==========================================
            ['immatriculation' => '1237 TBA', 'marque' => 'Yamaha', 'modele' => 'Sirius', 'couleur' => 'Blanc',    'type_vehicule' => 'moto', 'statut' => 'en_reparation'], // En panne
            ['immatriculation' => '1238 TBA', 'marque' => 'TVS',    'modele' => 'Star City', 'couleur' => 'Rouge',   'type_vehicule' => 'moto', 'statut' => 'disponible'],   // Pas de conducteur
            ['immatriculation' => '1239 TBA', 'marque' => 'Honda',  'modele' => 'Wave 125', 'couleur' => 'Gris',    'type_vehicule' => 'moto', 'statut' => 'en_entretien'],  // Entretien
            ['immatriculation' => '1240 TBA', 'marque' => 'Bajaj',  'modele' => 'Boxer',    'couleur' => 'Noir',    'type_vehicule' => 'moto', 'statut' => 'accidentee'],   // Accidentée

            // ==========================================
            // VOITURES (Optionnel, pour vos stats globales)
            // ==========================================
            ['immatriculation' => '7001 TBB', 'marque' => 'Toyota', 'modele' => 'Hiace', 'couleur' => 'Blanc',  'type_vehicule' => 'voiture', 'statut' => 'en_circulation'],
        ];

        foreach ($motos as $i => $data) {
            Moto::create(array_merge($data, [
                'annee_fabrication' => rand(2016, 2023),
                'numero_chassis' => 'CH' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                'numero_moteur' => 'MT' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                'date_achat' => now()->subMonths(rand(8, 24)), // Acheté il y a au moins 8 mois pour avoir un historique
                
                // Prix d'achat adapté
                'prix_achat' => $data['type_vehicule'] === 'voiture' ? 40000000 : 6400000,
                
                // Montant de versement mensuel (600 000 Ar par défaut pour les motos)
                'montant_versement_mensuel' => $data['type_vehicule'] === 'voiture' ? 0 : 600000,
                'montant_versement_journalier' => $data['type_vehicule'] === 'voiture' ? 100000 : 0,
                
                'actif' => $data['statut'] !== 'vendue',
            ]));
        }
    }
}