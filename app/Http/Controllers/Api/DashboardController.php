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
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Vue d'ensemble (KPIs, Retards, Avances)
     * Filtres: day, week, month, year, custom (start_date, end_date)
     */
    public function index()
    {
        // Récupération des dates selon le filtre passé en paramètre (GET)
        $dates = $this->getDateRange(request('period', 'month'), request('start_date'), request('end_date'));
        $start = $dates['start'];
        $end = $dates['end'];

        // Clé de cache basée sur la période demandée (pour éviter de taper la DB à chaque refresh)
        $cacheKey = 'dashboard_kpis_' . md5($start . $end);
        
        $data = Cache::remember($cacheKey, 300, function () use ($start, $end) {
            
            // Calcul des sommes financières pour la période donnée
            $revenus = (float) Versement::whereBetween('date_versement', [$start, $end])->sum('montant_verse');
            $depenses = (float) Depense::whereBetween('date_depense', [$start, $end])->sum('montant')
                + (float) Reparation::whereBetween('date_reparation', [$start, $end])->sum('montant');

            return [
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
                    'en_retard_paiement' => Versement::where('en_retard', true)
                                                    ->distinct('conducteur_id')
                                                    ->count('conducteur_id'),
                ],
                'finances' => [
                    'revenus_total_motos' => $revenus,
                    'depenses_du_mois' => $depenses,
                    'benefices_du_mois' => $revenus - $depenses,
                    'total_avances' => (float) Avance::where('type', 'avance')->sum('solde'),
                    'total_provisions' => (float) Avance::where('type', 'provision')->sum('solde'),
                    
                    'revenus_total_global' => (float) Versement::sum('montant_verse'),
                    'depenses_total_global' => (float) Depense::sum('montant') + (float) Reparation::sum('montant'),
                ],
            ];
        });

        // CORRECTION ICI : Appel de la méthode renommée
        return $this->successResponse($data);
    }

    /**
     * Données pour les graphiques.
     */
    public function graphiques()
    {
        $dates = $this->getDateRange(request('period', 'year'), request('start_date'), request('end_date'));
        $start = $dates['start'];
        $end = $dates['end'];
    
        $cacheKey = 'dashboard_graphs_' . md5($start . $end);
        
        $data = Cache::remember($cacheKey, 600, function () use ($start, $end) {
            // 1. Revenus mensuels (CORRIGÉ POUR POSTGRESQL)
            $revenusMensuels = Versement::selectRaw("TO_CHAR(date_versement, 'YYYY-MM') as periode, SUM(montant_verse) as total")
                ->whereBetween('date_versement', [$start, $end])
                ->groupBy('periode')->orderBy('periode')->get();
    
            // 2. Dépenses mensuelles (CORRIGÉ POUR POSTGRESQL)
            $depensesMensuelles = Depense::selectRaw("TO_CHAR(date_depense, 'YYYY-MM') as periode, SUM(montant) as total")
                ->whereBetween('date_depense', [$start, $end])
                ->groupBy('periode')->orderBy('periode')->get();
    
            // 3. Répartition des dépenses
            $repartitionDepenses = Depense::selectRaw('categorie, SUM(montant) as total')
                ->whereBetween('date_depense', [$start, $end])
                ->groupBy('categorie')->orderByDesc('total')->get();
    
            // 4. Motos les plus rentables
            $motosRentables = Moto::with(['versements' => function($query) use ($start, $end) {
                    $query->whereBetween('date_versement', [$start, $end]);
                }, 'depenses' => function($query) use ($start, $end) {
                    $query->whereBetween('date_depense', [$start, $end]);
                }, 'reparations' => function($query) use ($start, $end) {
                    $query->whereBetween('date_reparation', [$start, $end]);
                }])
                ->get()
                ->map(function (Moto $moto) {
                    $revenu = $moto->versements->sum('montant_verse');
                    $depense = $moto->depenses->sum('montant') + $moto->reparations->sum('montant');
                    return [
                        'moto' => $moto->immatriculation,
                        'benefice' => $revenu - $depense,
                    ];
                })
                ->sortByDesc('benefice')
                ->values()
                ->take(10);
    
            // 5. Évolution des bénéfices
            $evolutionBenefices = $revenusMensuels->map(function ($revItem) use ($depensesMensuelles) {
                $periode = $revItem->periode;
                $depItem = $depensesMensuelles->firstWhere(function ($d) use ($periode) {
                    return $d->periode == $periode;
                });
                return [
                    'periode' => $periode,
                    'benefice' => $revItem->total - ($depItem->total ?? 0),
                    'revenus' => $revItem->total,
                    'depenses' => $depItem->total ?? 0,
                ];
            })->values();
    
            return [
                'revenus_mensuels' => $revenusMensuels,
                'depenses_mensuelles' => $depensesMensuelles,
                'evolution_benefices' => $evolutionBenefices,
                'repartition_depenses' => $repartitionDepenses,
                'motos_les_plus_rentables' => $motosRentables,
            ];
        });
    
        return $this->successResponse($data);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Calcule les dates de début et fin selon le filtre.
     */
    private function getDateRange($period, $startDate = null, $endDate = null)
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;
            case 'week':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                break;
            case 'month':
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                break;
            case 'year':
                $start = now()->startOfYear();
                $end = now()->endOfYear();
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                }
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Format de réponse standardisé pour l'API.
     * CORRECTION : Renommé en successResponse pour éviter le conflit avec la classe parente.
     */
    private function successResponse($data)
    {
        return response()->json(['success' => true, 'data' => $data]);
    }
}