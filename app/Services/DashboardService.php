<?php

namespace App\Services;

use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Versement;
use App\Models\Depense;
use App\Models\Reparation;
use App\Models\Avance;
use App\Models\Absence;
use App\Models\Affectation;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function fullDashboard($start, $end)
    {
        $key = "dashboard:full:" . md5($start . $end);
        return Cache::remember($key, 600, function () use ($start, $end) {
            
            // 1. KPIs globaux
            $kpis = $this->kpis($start, $end);

            // 2. Graphiques
            $graphs = $this->graphiques($start, $end);

            // 3. Détails par moto
            $motosDetails = $this->motosDetails();

            // 4. Conducteurs détaillés
            $conducteursDetails = $this->conducteursDetails();

            // 5. Alertes
            $alertes = $this->alertes();

            // 6. Véhicules actifs
            $vehiculesActifs = $this->vehiculesActifs();

            return [
                'kpis' => $kpis,
                'graphiques' => $graphs,
                'motos_details' => $motosDetails,
                'conducteurs_details' => $conducteursDetails,
                'alertes' => $alertes,
                'vehicules_actifs' => $vehiculesActifs,
            ];
        });
    }

    // ------------------------------------------------------------
    // Sous‑méthodes
    // ------------------------------------------------------------

    private function kpis($start, $end)
    {
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
    }

    private function graphiques($start, $end)
    {
        $revenus = Versement::selectRaw("
            TO_CHAR(date_versement, 'YYYY-MM') as periode,
            SUM(montant_verse) as total
        ")->whereBetween('date_versement', [$start, $end])
        ->groupBy('periode')->orderBy('periode')->get();

        $depenses = Depense::selectRaw("
            TO_CHAR(date_depense, 'YYYY-MM') as periode,
            SUM(montant) as total
        ")->whereBetween('date_depense', [$start, $end])
        ->groupBy('periode')->orderBy('periode')->get();

        $categories = Depense::selectRaw("
            categorie, SUM(montant) as total
        ")->whereBetween('date_depense', [$start, $end])
        ->groupBy('categorie')->orderByDesc('total')->get();

        $depensesMap = $depenses->pluck('total', 'periode')->toArray();
        $benefices = $revenus->map(fn($rev) => [
            "periode" => $rev->periode,
            "benefice" => $rev->total - ($depensesMap[$rev->periode] ?? 0)
        ])->values();

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
        ];
    }

    private function motosDetails()
    {
        return Moto::with(['versements', 'depenses', 'reparations', 'affectationActive.conducteur'])
            ->get()
            ->map(function ($moto) {
                $revenusGlobal = $moto->versements->sum('montant_verse');
                $depensesGlobal = $moto->depenses->sum('montant') + $moto->reparations->sum('montant');
                $beneficeGlobal = $revenusGlobal - $depensesGlobal;

                $graphique = $moto->versements
                    ->groupBy(fn($v) => $v->date_versement->format('Y-m'))
                    ->map(fn($group) => $group->sum('montant_verse'))
                    ->toArray();

                return [
                    'immatriculation' => $moto->immatriculation,
                    'modele' => $moto->modele,
                    'statut' => $moto->statut,
                    'conducteur_actuel' => $moto->affectationActive?->conducteur?->nom,
                    'revenus_total' => $revenusGlobal,
                    'depenses_total' => $depensesGlobal,
                    'benefice_total' => $beneficeGlobal,
                    'graphique_evolution' => $graphique,
                ];
            });
    }

    private function conducteursDetails()
    {
        return Conducteur::with(['absences', 'versements', 'moto'])
            ->get()
            ->map(function ($conducteur) {
                $totalAbsences = $conducteur->absences->sum('nombre_jours');
                $totalRetenues = $conducteur->absences->sum('retenue');
                $totalVersements = $conducteur->versements->sum('montant_verse');

                return [
                    'nom' => $conducteur->nom,
                    'prenom' => $conducteur->prenom,
                    'statut' => $conducteur->statut,
                    'moto_actuelle' => $conducteur->moto?->immatriculation,
                    'total_absences_jours' => $totalAbsences,
                    'total_retenues' => $totalRetenues,
                    'total_versements' => $totalVersements,
                ];
            });
    }

    private function alertes()
    {
        $alertes = [];

        // Motos en panne / réparation
        $motosPanne = Moto::whereIn('statut', ['en_reparation', 'accidentee', 'hors_service'])
            ->get(['immatriculation', 'statut']);
        foreach ($motosPanne as $moto) {
            $alertes[] = [
                'type' => 'panne',
                'message' => "Moto {$moto->immatriculation} : {$moto->statut}"
            ];
        }

        // Conducteurs avec dette
        $retards = Conducteur::join('versements', 'conducteurs.id', '=', 'versements.conducteur_id')
            ->where('versements.en_retard', true)
            ->selectRaw('conducteurs.nom, conducteurs.prenom, SUM(versements.reste_a_payer) as dette')
            ->groupBy('conducteurs.id', 'conducteurs.nom', 'conducteurs.prenom')
            ->get();
        foreach ($retards as $retard) {
            $alertes[] = [
                'type' => 'retard_paiement',
                'message' => "{$retard->nom} {$retard->prenom} : dette de " . number_format($retard->dette, 0, ',', ' ') . " Ar"
            ];
        }

        return $alertes;
    }

    private function vehiculesActifs()
    {
        return Affectation::where('active', true)
            ->with(['moto', 'conducteur'])
            ->get()
            ->map(fn($a) => [
                'type' => $a->moto->type_vehicule ?? 'moto',
                'nom' => $a->moto->immatriculation,
                'conducteur' => $a->conducteur->nom . ' ' . $a->conducteur->prenom
            ]);
    }
}