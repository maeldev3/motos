<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Versement;
use App\Models\Alerte;
class AlerteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Alerte pour chaque moto qui n'est pas en circulation normale
          Moto::whereIn('statut', ['en_reparation', 'accidentee', 'en_entretien', 'hors_service'])
          ->get()
          ->each(function (Moto $moto) {
              $type = match ($moto->statut) {
                  'en_reparation', 'accidentee' => 'moto_en_panne',
                  'en_entretien' => 'entretien_moto',
                  default => 'entretien_moto',
              };

              Alerte::create([
                  'type' => $type,
                  'alertable_type' => Moto::class,
                  'alertable_id' => $moto->id,
                  'message' => "La moto {$moto->immatriculation} est en statut '{$moto->statut}'",
                  'lue' => false,
              ]);
          });

      // Alerte pour chaque versement réellement en retard
      Versement::where('en_retard', true)
          ->with('moto')
          ->get()
          ->unique('moto_id')
          ->each(function ($versement) {
              Alerte::create([
                  'type' => 'versement_retard',
                  'alertable_type' => Moto::class,
                  'alertable_id' => $versement->moto_id,
                  'message' => "Versement en retard pour la moto {$versement->moto->immatriculation}",
                  'lue' => false,
              ]);
          });

      // Alerte pour les conducteurs suspendus (absence prolongée / discipline)
      Conducteur::where('statut', 'suspendu')
          ->get()
          ->each(function (Conducteur $conducteur) {
              Alerte::create([
                  'type' => 'conducteur_absent',
                  'alertable_type' => Conducteur::class,
                  'alertable_id' => $conducteur->id,
                  'message' => "Le conducteur {$conducteur->prenom} {$conducteur->nom} est suspendu",
                  'lue' => false,
              ]);
          });
  
    }
}
