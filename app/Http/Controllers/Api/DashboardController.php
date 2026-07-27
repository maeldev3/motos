<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Avance;
use App\Models\Conducteur;
use App\Models\Depense;
use App\Models\Moto;
use App\Models\Reparation;
use App\Models\Versement;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Vue d'ensemble temps réel (section 12 du cahier des charges).
     */
    public function index()
    {
        $debutMois = now()->startOfMonth();
        $finMois = now()->endOfMonth();

        $revenusMois = (float) Versement::whereBetween('date_versement', [$debutMois, $finMois])->sum('montant_verse');
        $depensesMois = (float) Depense::whereBetween('date_depense', [$debutMois, $finMois])->sum('montant')
            + (float) Reparation::whereBetween('date_reparation', [$debutMois, $finMois])->sum('montant');

        return $this->ok([
            'motos' => [
                'total' => Moto::count(),
                'disponibles' => Moto::where('statut', 'disponible')->count(),
                'en_reparation' => Moto::where('statut', 'en_reparation')->count(),
                'hors_service' => Moto::where('statut', 'hors_service')->count(),
                'en_circulation' => Moto::where('statut', 'en_circulation')->count(),
            ],
            'conducteurs' => [
                'total' => Conducteur::count(),
                'actifs' => Conducteur::where('statut', 'actif')->count(),
                'suspendus' => Conducteur::where('statut', 'suspendu')->count(),
                'absents_aujourdhui' => Absence::whereDate('date_debut', '<=', now())->whereDate('date_fin', '>=', now())->count(),
                'en_retard_paiement' => Versement::where('en_retard', true)->distinct('conducteur_id')->count('conducteur_id'),
            ],
            'finances' => [
                'versements_du_jour' => (float) Versement::whereDate('date_versement', now())->sum('montant_verse'),
                'revenus_du_mois' => $revenusMois,
                'depenses_du_mois' => $depensesMois,
                'benefices_du_mois' => $revenusMois - $depensesMois,
                'total_avances' => (float) Avance::where('type', 'avance')->sum('solde'),
                'total_provisions' => (float) Avance::where('type', 'provision')->sum('solde'),
            ],
        ]);
    }

    /**
     * Données pour les graphiques du tableau de bord.
     */
    public function graphiques()
    {
        $annee = now()->year;

        $revenusMensuels = Versement::selectRaw('EXTRACT(MONTH FROM date_versement) as mois, SUM(montant_verse) as total')
            ->whereYear('date_versement', $annee)
            ->groupBy('mois')->orderBy('mois')->get();

        $depensesMensuelles = Depense::selectRaw('EXTRACT(MONTH FROM date_depense) as mois, SUM(montant) as total')
            ->whereYear('date_depense', $annee)
            ->groupBy('mois')->orderBy('mois')->get();

        $repartitionDepenses = Depense::selectRaw('categorie, SUM(montant) as total')
            ->whereYear('date_depense', $annee)
            ->groupBy('categorie')->orderByDesc('total')->get();

        $motosRentables = Moto::with([])->get()->map(function (Moto $moto) {
            $debut = now()->startOfYear();
            $fin = now()->endOfYear();

            return [
                'moto' => $moto->immatriculation,
                'benefice_annuel' => $moto->beneficeEntre($debut, $fin),
            ];
        })->sortByDesc('benefice_annuel')->values()->take(10);

        return $this->ok([
            'revenus_mensuels' => $revenusMensuels,
            'depenses_mensuelles' => $depensesMensuelles,
            'repartition_depenses' => $repartitionDepenses,
            'motos_les_plus_rentables' => $motosRentables,
        ]);
    }
}
