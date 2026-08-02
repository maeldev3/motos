<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conducteur;

class ConducteurSeeder extends Seeder
{
    public function run(): void
    {
        // Seulement 3 conducteurs actifs pour les 3 motos "en_circulation", + 1 en réparation
        $conducteurs = [
            ['nom' => 'Njaka', 'prenom' => 'Beau frere', 'sexe' => 'homme', 'telephone' => '0383369799', 'statut' => 'actif'],
            ['nom' => 'Jese', 'prenom' => 'Nekena', 'sexe' => 'homme', 'telephone' => '0380742658', 'statut' => 'actif'],
            ['nom' => 'Fihorenana', 'prenom' => 'Beloha', 'sexe' => 'homme', 'telephone' => '0382840649', 'statut' => 'actif'],
            
            // Inactifs / Suspendus pour les motos indisponibles
            ['nom' => 'Manitra', 'prenom' => 'Zandrilah', 'sexe' => 'homme', 'telephone' => '0383556449', 'statut' => 'inactif'],
            ['nom' => 'Teo', 'prenom' => 'akama', 'sexe' => 'homme', 'telephone' => '0341234505', 'statut' => 'inactif'],
        ];

        foreach ($conducteurs as $i => $data) {
            Conducteur::create(array_merge($data, [
                'date_naissance' => now()->subYears(rand(22, 45))->subDays(rand(0, 365)),
                'adresse' => 'Antananarivo, Madagascar',
                'cin' => '10' . str_pad((string) rand(100000000, 999999999), 9, '0'),
                'numero_permis' => 'PM' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'date_embauche' => now()->subMonths(rand(2, 24)),
                'contact_urgence_nom' => 'Famille ' . $data['nom'],
                'contact_urgence_telephone' => '033' . rand(1000000, 9999999),
            ]));
        }
    }
}