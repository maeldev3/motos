<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Versement;
use App\Models\Absence;
use App\Models\Alerte;
use Carbon\Carbon;

class AlerteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===================================================================
        // 1. ALERTES POUR LES MOTOS EN PANNE OU HORS SERVICE
        // ===================================================================
        Moto::whereIn('statut', ['en_reparation', 'accidentee', 'en_entretien', 'hors_service'])
            ->get()
            ->each(function (Moto $moto) {
                $type = match ($moto->statut) {
                    'en_reparation', 'accidentee' => 'moto_en_panne',
                    'en_entretien' => 'entretien_moto',
                    default => 'entretien_moto',
                };

                // Message personnalisé selon le statut
                $message = match ($moto->statut) {
                    'accidentee' => "🚨 La moto {$moto->immatriculation} est accidentée. Nécessite une réparation urgente.",
                    'en_reparation' => "🔧 La moto {$moto->immatriculation} est actuellement en réparation au garage.",
                    'en_entretien' => "🛠️ La moto {$moto->immatriculation} est en entretien préventif.",
                    default => "⚠️ La moto {$moto->immatriculation} est hors service.",
                };

                Alerte::create([
                    'type' => $type,
                    'alertable_type' => Moto::class,
                    'alertable_id' => $moto->id,
                    'message' => $message,
                    'lue' => false,
                    'created_at' => Carbon::now()->subDays(rand(1, 3)), // Alerte récente
                ]);
            });

        // ===================================================================
        // 2. ALERTES POUR LES VERSEMENTS EN RETARD (1 SEULE PAR MOTO)
        // ===================================================================
        // On regroupe les versements en retard par moto, et on prend le plus ancien
        Versement::where('en_retard', true)
            ->with('moto', 'conducteur')
            ->get()
            ->groupBy('moto_id')
            ->each(function ($versements, $motoId) {
                $firstRetard = $versements->sortBy('date_versement')->first();
                $moto = $firstRetard->moto;
                $conducteur = $firstRetard->conducteur;
                
                // Calcul du montant total manquant pour cette moto
                $totalRestant = $versements->sum('reste_a_payer');

                Alerte::create([
                    'type' => 'versement_retard',
                    'alertable_type' => Moto::class,
                    'alertable_id' => $motoId,
                    'message' => "⚠️ Le conducteur {$conducteur->nom} {$conducteur->prenom} a un retard de paiement de " . number_format($totalRestant, 0, '', ' ') . " Ar pour la moto {$moto->immatriculation}.",
                    'lue' => false,
                    'created_at' => $firstRetard->date_versement->addDays(5), // L'alerte apparaît 5 jours après le retard
                ]);
            });

        // ===================================================================
        // 3. ALERTES POUR LES CONDUCTEURS SUSPENDUS
        // ===================================================================
        Conducteur::where('statut', 'suspendu')
            ->get()
            ->each(function (Conducteur $conducteur) {
                Alerte::create([
                    'type' => 'conducteur_absent',
                    'alertable_type' => Conducteur::class,
                    'alertable_id' => $conducteur->id,
                    'message' => "🚫 Le conducteur {$conducteur->prenom} {$conducteur->nom} a été suspendu. Vérifiez son dossier.",
                    'lue' => false,
                    'created_at' => Carbon::now()->subDays(2),
                ]);
            });

        // ===================================================================
        // 4. NOUVEAU : ALERTES POUR LES ABSENCES (MALADIE / ACCIDENT)
        // ===================================================================
        // On récupère les absences récentes (moins de 7 jours) pour alerter le gestionnaire
        Absence::where('date_fin', '>=', Carbon::now()->subDays(7))
            ->with('conducteur')
            ->get()
            ->each(function (Absence $absence) {
                $conducteur = $absence->conducteur;
                $dateDebut = $absence->date_debut->format('d/m/Y');
                $dateFin = $absence->date_fin->format('d/m/Y');

                // Message personnalisé selon le type d'absence
                $message = match ($absence->type) {
                    'maladie' => "🩺 Le conducteur {$conducteur->prenom} {$conducteur->nom} est en arrêt maladie du {$dateDebut} au {$dateFin}.",
                    'accident' => "🚑 Le conducteur {$conducteur->prenom} {$conducteur->nom} a eu un accident et est absent du {$dateDebut} au {$dateFin}.",
                    'conge' => "🏖️ Le conducteur {$conducteur->prenom} {$conducteur->nom} est en congé du {$dateDebut} au {$dateFin}.",
                    default => "📋 Le conducteur {$conducteur->prenom} {$conducteur->nom} est absent du {$dateDebut} au {$dateFin}.",
                };

                Alerte::create([
                    'type' => 'conducteur_absent',
                    'alertable_type' => Conducteur::class,
                    'alertable_id' => $conducteur->id,
                    'message' => $message,
                    'lue' => false,
                    'created_at' => $absence->date_debut->subDay(), // Alerte créée la veille de l'absence
                ]);
            });
    }
}