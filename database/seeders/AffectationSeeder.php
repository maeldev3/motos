<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\Conducteur;
use App\Models\Moto;
use Illuminate\Database\Seeder;

class AffectationSeeder extends Seeder
{
    public function run(): void
    {
        $affectations = [
            ['conducteur' => 'Njaka', 'immatriculation' => '0001 AAA', 'date_debut' => '2026-02-15'],
            ['conducteur' => 'Jese', 'immatriculation' => '0002 BBA', 'date_debut' => '2026-06-01'],
            ['conducteur' => 'Fihorenana', 'immatriculation' => '0003 CCC', 'date_debut' => '2026-04-01'],
            ['conducteur' => 'Manana', 'immatriculation' => '0004 DDD', 'date_debut' => '2026-10-11'],
        ];

        foreach ($affectations as $item) {
            $conducteur = Conducteur::where('nom', $item['conducteur'])->first();
            $moto = Moto::where('immatriculation', $item['immatriculation'])->first();
            if (!$conducteur || !$moto) {
                continue;
            }

            Affectation::where('conducteur_id', $conducteur->id)
                ->where('active', true)
                ->where('moto_id', '!=', $moto->id)
                ->update(['active' => false, 'date_fin' => now()->toDateString()]);

            Affectation::where('moto_id', $moto->id)
                ->where('active', true)
                ->where('conducteur_id', '!=', $conducteur->id)
                ->update(['active' => false, 'date_fin' => now()->toDateString()]);

            Affectation::updateOrCreate(
                ['moto_id' => $moto->id, 'conducteur_id' => $conducteur->id, 'active' => true],
                ['date_debut' => $item['date_debut'], 'date_fin' => null, 'motif_changement' => 'Affectation initiale']
            );

            $conducteur->update(['moto_id' => $moto->id]);
        }
    }
}
