<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MotoFactory extends Factory
{
    protected $model = \App\Models\Moto::class;

    public function definition(): array
    {
        return [
            'immatriculation' => strtoupper(fake()->bothify('####-???')),
            'marque' => fake()->randomElement(['Yamaha', 'Honda', 'Suzuki', 'TVS']),
            'modele' => fake()->word(),
            'couleur' => fake()->safeColorName(),
            'annee_fabrication' => fake()->numberBetween(2015, 2025),
            'date_achat' => fake()->date(),
            'prix_achat' => fake()->numberBetween(2000000, 8000000),
            'type_vehicule' => 'moto',
            'montant_versement_mensuel' => 600000,
            'montant_versement_journalier' => 0,
            'statut' => 'disponible',
            'actif' => true,
        ];
    }
}
