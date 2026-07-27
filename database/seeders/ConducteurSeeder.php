<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Conducteur;

class ConducteurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conducteurs = [
            ['nom' => 'Andriamampianina', 'prenom' => 'Jean', 'sexe' => 'homme', 'telephone' => '0341234501', 'statut' => 'actif'],
            ['nom' => 'Razafindrakoto', 'prenom' => 'Paul', 'sexe' => 'homme', 'telephone' => '0341234502', 'statut' => 'actif'],
            ['nom' => 'Rasoamanana', 'prenom' => 'Marie', 'sexe' => 'femme', 'telephone' => '0341234503', 'statut' => 'actif'],
            ['nom' => 'Rakotondrabe', 'prenom' => 'Eric', 'sexe' => 'homme', 'telephone' => '0341234504', 'statut' => 'actif'],
            ['nom' => 'Ravaomanana', 'prenom' => 'Nirina', 'sexe' => 'femme', 'telephone' => '0341234505', 'statut' => 'actif'],
            ['nom' => 'Rabearison', 'prenom' => 'Tojo', 'sexe' => 'homme', 'telephone' => '0341234506', 'statut' => 'suspendu'],
            ['nom' => 'Randrianasolo', 'prenom' => 'Hery', 'sexe' => 'homme', 'telephone' => '0341234507', 'statut' => 'actif'],
            ['nom' => 'Rakotoarisoa', 'prenom' => 'Voahangy', 'sexe' => 'femme', 'telephone' => '0341234508', 'statut' => 'inactif'],
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
                // moto_id sera mis à jour par AffectationSeeder pour rester cohérent
            ]));
        }
    }
}
