<?php

namespace App\Console\Commands;

use App\Models\Affectation;
use App\Models\Alerte;
use App\Models\Versement;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenererNotificationsVersements extends Command
{
    protected $signature = 'notifications:versements';
    protected $description = 'Génère les alertes des échéances de versement proches ou dépassées';

    public function handle(): int
    {
        $affectations = Affectation::query()
            ->where('active', true)
            ->with(['moto', 'conducteur'])
            ->get();

        foreach ($affectations as $affectation) {
            $moto = $affectation->moto;
            $conducteur = $affectation->conducteur;
            if (!$moto || !$conducteur) {
                continue;
            }

            $periodicite = $moto->periodicite_versement ?: 'mensuel';
            $dernier = Versement::where('moto_id', $moto->id)
                ->where('conducteur_id', $conducteur->id)
                ->latest('date_versement')
                ->first();

            $base = $dernier?->date_versement
                ? Carbon::parse($dernier->date_versement)
                : Carbon::parse($affectation->date_debut);

            $echeance = $periodicite === 'hebdomadaire'
                ? $base->copy()->addWeek()
                : $base->copy()->addMonthNoOverflow();

            if ($echeance->gt(now()->addDays(2)) || $echeance->lt(now()->subMonths(2))) {
                continue;
            }

            $montant = $periodicite === 'hebdomadaire'
                ? (float) $moto->montant_versement_hebdomadaire
                : (float) $moto->montant_versement_mensuel;

            $type = 'versement_retard';
            $message = sprintf(
                'Rappel de versement %s : %s %s — moto %s (%s), échéance %s, montant prévu %s Ar.',
                $periodicite,
                $conducteur->prenom,
                $conducteur->nom,
                $moto->immatriculation,
                $moto->marque . ' ' . $moto->modele,
                $echeance->format('d/m/Y'),
                number_format($montant, 0, ',', ' ')
            );

            $existe = Alerte::where('type', $type)
                ->where('alertable_type', get_class($moto))
                ->where('alertable_id', $moto->id)
                ->where('message', $message)
                ->whereDate('created_at', today())
                ->exists();

            if (!$existe) {
                Alerte::create([
                    'type' => $type,
                    'alertable_type' => get_class($moto),
                    'alertable_id' => $moto->id,
                    'message' => $message,
                    'lue' => false,
                ]);
            }
        }

        $this->info('Vérification des échéances terminée.');
        return self::SUCCESS;
    }
}
