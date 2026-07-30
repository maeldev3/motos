<?php

namespace App\Services;

use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Versement;
use App\Models\Depense;
use App\Models\Reparation;
use App\Models\Avance;
use App\Models\Affectation;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    // =====================================================
    // 1. KPIs PRINCIPAUX
    // =====================================================
    public function kpis($start, $end)
    {
        $key = "dashboard:kpi:" . md5($start . $end);
        return Cache::remember($key, 300, function () use ($start, $end) {
            
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

            $revenus = Versement::whereBetween('date_versement', [$start, $end])->sum('montant_verse');
            $depenses = Depense::whereBetween('date_depense', [$start, $end])->sum('montant')
                + Reparation::whereBetween('date_reparation', [$start, $end])->sum('montant');

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
                    "depenses" => (float) $depenses,
                    "benefice" => (float) ($revenus - $depenses),
                    "avances" => $avances,
                    "provisions" => $provisions
                ]
            ];
        });
    }

    // =====================================================
    // 2. GRAPHIQUES & ANALYSES
    // =====================================================
    public function graphiques($start, $end)
    {
        $key = "dashboard:graphs:" . md5($start . $end);
        return Cache::remember($key, 600, function () use ($start, $end) {
            
            $revenus = Versement::selectRaw("
                TO_CHAR(date_versement, 'YYYY-MM') as periode,
                SUM(montant_verse) as total
            ")
            ->whereBetween('date_versement', [$start, $end])
            ->groupBy('periode')->orderBy('periode')->get();

            $depenses = Depense::selectRaw("
                TO_CHAR(date_depense, 'YYYY-MM') as periode,
                SUM(montant) as total
            ")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('periode')->orderBy('periode')->get();

            $categories = Depense::selectRaw("
                categorie, SUM(montant) as total
            ")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('categorie')->orderByDesc('total')->get();

            $depensesMap = $depenses->pluck('total', 'periode')->toArray();
            $benefices = $revenus->map(function ($rev) use ($depensesMap) {
                return [
                    "periode" => $rev->periode,
                    "benefice" => $rev->total - ($depensesMap[$rev->periode] ?? 0)
                ];
            })->values();

            $topMotos = Moto::query()
                ->select('motos.immatriculation')
                ->selectRaw("
                    COALESCE(SUM(versements.montant_verse), 0) - COALESCE(SUM(depenses.montant), 0) - COALESCE(SUM(reparations.montant), 0) as benefice
                ")
                ->leftJoin('versements', 'motos.id', '=', 'versements.moto_id')
                ->leftJoin('depenses', 'motos.id', '=', 'depenses.moto_id')
                ->leftJoin('reparations', 'motos.id', '=', 'reparations.moto_id')
                ->groupBy('motos.id', 'motos.immatriculation')
                ->orderByDesc('benefice')
                ->limit(10)
                ->get();

            return [
                "revenus_mensuels" => $revenus,
                "depenses_mensuelles" => $depenses,
                "evolution_benefices" => $benefices,
                "repartition_depenses" => $categories,
                "top_motos_rentables" => $topMotos,
                // "retards_paiement" => $this->retardsPaiementDetail()
            ];
        });
    }

    // =====================================================
    // 3. VÉHICULES ACTIFS (Section du bas de la maquette)
    // =====================================================
    public function vehiculesActifs()
    {
        return Affectation::where('active', true)
            ->with(['moto', 'conducteur'])
            ->get()
            ->map(fn($a) => [
                'type' => $a->moto->type_vehicule ?? 'moto',
                'nom'  => $a->moto->immatriculation,
                'conducteur' => $a->conducteur->nom . ' ' . $a->conducteur->prenom
            ]);
    }

    // =====================================================
    // 4. CONDUCTEURS EN RETARD DÉTAILLÉS
    // =====================================================
    public function retardsPaiementDetail()
    {
        return Conducteur::query()
            ->join('versements', 'conducteurs.id', '=', 'versements.conducteur_id')
            ->where('versements.en_retard', true)
            ->selectRaw("
                conducteurs.nom,
                conducteurs.prenom,
                SUM(versements.reste_a_payer) as dette
            ")
            ->groupBy('conducteurs.id', 'conducteurs.nom', 'conducteurs.prenom')
            ->orderByDesc('dette')
            ->limit(10)
            ->get();
    }
}