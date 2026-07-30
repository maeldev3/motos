<?php

namespace App\Services;

use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Versement;
use App\Models\Depense;
use App\Models\Reparation;
use App\Models\Avance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * =====================================================
     * KPI PRINCIPAUX
     * =====================================================
     */
    public function kpis($start, $end)
    {
        $key = "dashboard:kpi:" . md5($start . $end);

        return Cache::remember($key, 300, function () use ($start, $end) {
            
            // Utilisation des Index SQL via des agrégations optimisées
            $motos = Moto::selectRaw("
                COUNT(*) total,
                COUNT(*) FILTER(WHERE statut='disponible') disponibles,
                COUNT(*) FILTER(WHERE statut='en_reparation') reparation,
                COUNT(*) FILTER(WHERE statut='hors_service') hors_service,
                COUNT(*) FILTER(WHERE statut='en_circulation') circulation
            ")->first();

            $conducteurs = Conducteur::selectRaw("
                COUNT(*) total,
                COUNT(*) FILTER(WHERE statut='actif') actifs,
                COUNT(*) FILTER(WHERE statut='suspendu') suspendus
            ")->first();

            // Optimisation des sommes financières
            $revenus = Versement::whereBetween('date_versement', [$start, $end])->sum('montant_verse');
            $depenses = Depense::whereBetween('date_depense', [$start, $end])->sum('montant');
            $reparations = Reparation::whereBetween('date_reparation', [$start, $end])->sum('montant');
            $totalDepenses = $depenses + $reparations;

            // Somme globale des avances/provisions (utilise un index sur 'type')
            $avances = (float) Avance::where('type', 'avance')->sum('solde');
            $provisions = (float) Avance::where('type', 'provision')->sum('solde');

            return [
                "periode" => ["de" => $start->format('Y-m-d'), "a" => $end->format('Y-m-d')],
                "motos" => [
                    "total" => (int) $motos->total,
                    "disponibles" => (int) $motos->disponibles,
                    "reparation" => (int) $motos->reparation,
                    "hors_service" => (int) $motos->hors_service,
                    "circulation" => (int) $motos->circulation
                ],
                "conducteurs" => [
                    "total" => (int) $conducteurs->total,
                    "actifs" => (int) $conducteurs->actifs,
                    "suspendus" => (int) $conducteurs->suspendus
                ],
                "finance" => [
                    "revenus" => (float) $revenus,
                    "depenses" => (float) $totalDepenses,
                    "benefice" => (float) ($revenus - $totalDepenses),
                    "avances" => $avances,
                    "provisions" => $provisions
                ]
            ];
        });
    }

    /**
     * =====================================================
     * GRAPHIQUES (Optimisé avec Eager Loading & Index)
     * =====================================================
     */
    public function graphiques($start, $end)
    {
        $key = "dashboard:graphs:" . md5($start . $end);

        return Cache::remember($key, 600, function () use ($start, $end) {
            
            // 1. Revenus mensuels (Utilise l'index sur 'date_versement')
            $revenus = Versement::selectRaw("
                TO_CHAR(date_versement, 'YYYY-MM') as periode,
                SUM(montant_verse) as total
            ")
            ->whereBetween('date_versement', [$start, $end])
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

            // 2. Dépenses mensuelles (Utilise l'index sur 'date_depense')
            $depenses = Depense::selectRaw("
                TO_CHAR(date_depense, 'YYYY-MM') as periode,
                SUM(montant) as total
            ")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

            // 3. Répartition par catégorie (Utilise l'index sur 'categorie')
            $categories = Depense::selectRaw("
                categorie,
                SUM(montant) as total
            ")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('categorie')
            ->orderByDesc('total')
            ->get();

            // 4. Évolution des bénéfices (Optimisation du calcul)
            // On crée un Map pour un accès O(1) au lieu de boucler avec firstWhere()
            $depensesMap = $depenses->pluck('total', 'periode')->toArray();

            $benefices = $revenus->map(function ($rev) use ($depensesMap) {
                return [
                    "periode" => $rev->periode,
                    "benefice" => $rev->total - ($depensesMap[$rev->periode] ?? 0)
                ];
            })->values();

            // 5. Top 10 motos rentables (Utilise le cache interne de rentabiliteMotos)
            $topMotos = $this->rentabiliteMotos()->take(10);

            return [
                "revenus_mensuels" => $revenus,
                "depenses_mensuelles" => $depenses,
                "evolution_benefices" => $benefices,
                "repartition_depenses" => $categories,
                "top_motos_rentables" => $topMotos
            ];
        });
    }

    /**
     * =====================================================
     * RENTABILITE DEPUIS CREATION (Optimisé avec Index et Eager Loading)
     * =====================================================
     */
    public function rentabiliteMotos()
    {
        return Cache::remember('dashboard:rentabilite', 3600, function () {
            
            // Utilisation de SQL pur avec LEFT JOIN pour la performance.
            // Les index SQL sur 'versements.moto_id' et 'depenses.moto_id' sont utilisés.
            return Moto::query()
                ->select('motos.immatriculation')
                ->selectRaw("
                    COALESCE(SUM(versements.montant_verse), 0) - COALESCE(SUM(depenses.montant), 0) as benefice
                ")
                ->leftJoin('versements', 'motos.id', '=', 'versements.moto_id')
                ->leftJoin('depenses', 'motos.id', '=', 'depenses.moto_id')
                ->groupBy('motos.id', 'motos.immatriculation')
                ->orderByDesc('benefice')
                ->get();
        });
    }

    /**
     * =====================================================
     * REVENUS PAR MOTO
     * =====================================================
     */
    public function revenusMotos($start, $end)
    {
        return Versement::query()
            ->join('motos', 'motos.id', '=', 'versements.moto_id')
            ->selectRaw("motos.immatriculation, SUM(versements.montant_verse) as total")
            ->whereBetween('date_versement', [$start, $end])
            ->groupBy('motos.id', 'motos.immatriculation')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * =====================================================
     * DEPENSES PAR MOTO
     * =====================================================
     */
    public function depensesMotos($start, $end)
    {
        return Depense::query()
            ->join('motos', 'motos.id', '=', 'depenses.moto_id')
            ->selectRaw("motos.immatriculation, SUM(depenses.montant) as total")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('motos.id', 'motos.immatriculation')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * =====================================================
     * BENEFICES PAR MOTO
     * =====================================================
     */
    public function beneficesMotos($start, $end)
    {
        $revenus = $this->revenusMotos($start, $end)->pluck('total', 'immatriculation')->toArray();
        $depenses = $this->depensesMotos($start, $end)->pluck('total', 'immatriculation')->toArray();

        // Utilisation d'un Map pour éviter la boucle firstWhere
        return collect(array_keys($revenus))->map(function ($immatriculation) use ($revenus, $depenses) {
            return [
                "moto" => $immatriculation,
                "revenus" => $revenus[$immatriculation] ?? 0,
                "depenses" => $depenses[$immatriculation] ?? 0,
                "benefice" => ($revenus[$immatriculation] ?? 0) - ($depenses[$immatriculation] ?? 0)
            ];
        });
    }

    /**
     * =====================================================
     * CONDUCTEURS EN RETARD (Pagination & Cache ajoutés)
     * =====================================================
     */
    public function retardsPaiement($perPage = 20) // Ajout d'une pagination
    {
        // Clé de cache incluant la page pour éviter de charger tout en mémoire
        $key = 'dashboard:retards:' . $perPage;

        return Cache::remember($key, 300, function () use ($perPage) {
            return Conducteur::query()
                ->join('versements', 'conducteurs.id', '=', 'versements.conducteur_id')
                ->where('versements.en_retard', true)
                ->selectRaw("
                    conducteurs.id,
                    conducteurs.nom,
                    conducteurs.prenom,
                    COUNT(versements.id) as nombre_retard,
                    SUM(versements.reste_a_payer) as dette
                ")
                ->groupBy('conducteurs.id', 'conducteurs.nom', 'conducteurs.prenom')
                ->orderByDesc('dette')
                ->paginate($perPage); // Pagination pour ne pas saturer la mémoire
        });
    }
}