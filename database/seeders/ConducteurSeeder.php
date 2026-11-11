<?php

namespace Database\Seeders;

use App\Models\Conducteur;
use Illuminate\Database\Seeder;

class ConducteurSeeder extends Seeder
{
    public function run(): void
    {
        $conducteurs = [
            ['nom' => 'Njaka', 'prenom' => 'Beau-frère', 'telephone' => '0383369799', 'moto' => '0001 AAA'],
            ['nom' => 'Jese', 'prenom' => 'Nekena', 'telephone' => '0380742658', 'moto' => '0002 BBA'],
            ['nom' => 'Fihorenana', 'prenom' => 'Beloha', 'telephone' => '0382840649', 'moto' => '0003 CCC'],
            ['nom' => 'Manana', 'prenom' => 'Amis de Njaka', 'telephone' => '0340000004', 'moto' => '0004 DDD'],
        ];

        foreach ($conducteurs as $i => $data) {
            Conducteur::updateOrCreate(
                ['nom' => $data['nom']],
                [
                    'prenom' => $data['prenom'],
                    'sexe' => 'homme',
                    'telephone' => $data['telephone'],
                    'statut' => 'actif',
                    'date_naissance' => now()->subYears(30),
                    'adresse' => 'Antananarivo, Madagascar',
                    'cin' => '100000000000' . ($i + 1),
                    'numero_permis' => 'PM' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                    'date_embauche' => now()->subMonths(6),
                    'contact_urgence_nom' => 'Famille ' . $data['nom'],
                    'contact_urgence_telephone' => '033000000' . ($i + 1),
                ]
            );
        }
    }
}
