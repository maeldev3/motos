<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Affectation;

class AffectationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // On n'affecte que les motos disponibles pour circuler
          $motosDisponibles = Moto::whereIn('statut', ['en_circulation', 'disponible'])->get();
          $conducteursActifs = Conducteur::where('statut', 'actif')->get();
  
          $index = 0;
  
          foreach ($motosDisponibles as $moto) {
              if (!isset($conducteursActifs[$index])) {
                  break; // plus assez de conducteurs actifs
              }
  
              $conducteur = $conducteursActifs[$index];
  
              // Historique : un conducteur sur deux a eu une affectation précédente terminée
              if ($index % 2 === 0 && $index + 1 < $conducteursActifs->count()) {
                  Affectation::create([
                      'moto_id' => $moto->id,
                      'conducteur_id' => $conducteursActifs[$index + 1]->id,
                      'date_debut' => now()->subMonths(6),
                      'date_fin' => now()->subMonths(2),
                      'motif_changement' => 'Changement de véhicule',
                      'active' => false,
                  ]);
              }
  
              // Affectation active en cours
              Affectation::create([
                  'moto_id' => $moto->id,
                  'conducteur_id' => $conducteur->id,
                  'date_debut' => now()->subMonths(2),
                  'date_fin' => null,
                  'active' => true,
              ]);
  
              // Mise à jour de la référence rapide sur le conducteur
              $conducteur->update(['moto_id' => $moto->id]);
  
              $index++;
          }
    }
}
