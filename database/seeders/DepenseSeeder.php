<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Depense;

class DepenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $motos = Moto::where('actif', true)->get();

        $categoriesRecurrentes = ['carburant', 'lavage', 'parking'];

        foreach ($motos as $moto) {
            // Dépenses récurrentes logiques sur les 4 dernières semaines
            foreach ($categoriesRecurrentes as $categorie) {
                $montant = match ($categorie) {
                    'carburant' => $moto->type_vehicule === 'voiture' ? 80000 : 15000,
                    'lavage' => 3000,
                    'parking' => 2000,
                };

                Depense::create([
                    'moto_id' => $moto->id,
                    'date_depense' => now()->subWeeks(rand(1, 4)),
                    'categorie' => $categorie,
                    'montant' => $montant,
                    'commentaire' => ucfirst($categorie) . ' - ' . $moto->immatriculation,
                ]);
            }

            // Assurance annuelle sur toutes les motos actives
            Depense::create([
                'moto_id' => $moto->id,
                'date_depense' => now()->subMonths(2),
                'categorie' => 'assurance',
                'montant' => $moto->type_vehicule === 'voiture' ? 450000 : 120000,
                'commentaire' => 'Assurance annuelle',
            ]);
        }
    }
}
