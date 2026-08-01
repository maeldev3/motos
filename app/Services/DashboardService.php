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
use Illuminate\Support\Carbon;

/**
 * DashboardService
 * ------------------------------------------------------------
 * CORRECTIF (par rapport à la version précédente) :
 *
 *  -> Cause des "Server Error" sur /api/dashboard et
 *     /api/dashboard/motos-performance :
 *
 *     1) `->format('Y-m')` était appelé directement sur des colonnes
 *        nullable (date_versement, date_depense, date_reparation).
 *        Dès qu'une seule ligne avait une date NULL en base, PHP levait
 *        une Fatal Error "Call to a member function format() on null"
 *        (Error, pas juste un warning -> crash garanti, peu importe
 *        APP_DEBUG). C'était le cas dans motosPerformance() (dépenses /
 *        réparations, jamais formatées ailleurs) et dans motosDetails().
 *
 *     2) `vehiculesActifs()` faisait `$a->moto->immatriculation` et
 *        `$a->conducteur->nom` sans vérifier que la relation existe. Si
 *        une Affectation active pointe vers une moto ou un conducteur
 *        supprimé (soft delete) ou orphelin, l'accès à une propriété
 *        sur null déclenche un warning PHP. Or Laravel convertit les
 *        warnings PHP en ErrorException via son gestionnaire d'erreurs
 *        -> 500 "Server Error" en prod (APP_DEBUG=false masque le detail).
 *
 *     Ces deux endpoints étaient appelés par fullDashboard(), d'où le
 *     crash simultané de "/" et "/motos-performance".
 *
 *  -> Corrections apportées :
 *     - Toutes les collections sont filtrées pour exclure les lignes à
 *       date NULL avant tout ->format().
 *     - Tous les accès à des relations optionnelles (moto, conducteur,
 *       affectationActive) utilisent l'opérateur null-safe (?->) ou un
 *       filtre préalable.
 *     - vehiculesActifs() ignore désormais les affectations orphelines
 *       au lieu de planter.
 */
class DashboardService
{
    private const TTL = 600; // 10 minutes

    public function fullDashboard($start, $end)
    {
        $key = "dashboard:full:" . md5($start . $end);

        return Cache::remember($key, self::TTL, function () use ($start, $end) {
            return [
                'kpis'                 => $this->kpis($start, $end),
                'graphiques'           => $this->graphiques($start, $end),
                'motos_details'        => $this->motosDetails(),
                'conducteurs_details'  => $this->conducteursDetails(),
                'conducteurs_evolution'=> $this->conducteursEvolution($start, $end),
                'motos_performance'    => $this->motosPerformance($start, $end),
                'versements_resume'    => $this->versementsResume($start, $end),
                'alertes'              => $this->alertes(),
                'vehicules_actifs'     => $this->vehiculesActifs(),
                'modules'              => $this->modules(),
            ];
        });
    }

    // ------------------------------------------------------------
    // KPIs globaux
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

        $revenus = (float) Versement::whereBetween('date_versement', [$start, $end])->sum('montant_verse');
        $depenses = (float) Depense::whereBetween('date_depense', [$start, $end])->sum('montant')
            + (float) Reparation::whereBetween('date_reparation', [$start, $end])->sum('montant');

        $avances = (float) Avance::where('type', 'avance')->sum('solde');
        $provisions = (float) Avance::where('type', 'provision')->sum('solde');

        $enRetard = (int) Versement::whereBetween('date_versement', [$start, $end])->retard()->count();

        return [
            "periode" => ["de" => $start->format('Y-m-d'), "a" => $end->format('Y-m-d')],
            "motos" => [
                "total" => (int) $motos->total,
                "disponibles" => (int) $motos->disponibles,
                "reparation" => (int) $motos->reparation,
                "hors_service" => (int) $motos->hors_service,
                "circulation" => (int) $motos->circulation,
            ],
            "conducteurs" => [
                "total" => (int) $conducteurs->total,
                "actifs" => (int) $conducteurs->actifs,
                "suspendus" => (int) $conducteurs->suspendus,
                "en_retard_paiement" => $enRetard,
            ],
            "finance" => [
                "revenus" => $revenus,
                "depenses" => $depenses,
                "benefice" => $revenus - $depenses,
                "avances" => $avances,
                "provisions" => $provisions,
            ],
        ];
    }

    // ------------------------------------------------------------
    // Graphiques généraux
    // ------------------------------------------------------------

    private function graphiques($start, $end)
    {
        $revenus = Versement::selectRaw("TO_CHAR(date_versement, 'YYYY-MM') as periode, SUM(montant_verse) as total")
            ->whereBetween('date_versement', [$start, $end])
            ->groupBy('periode')->orderBy('periode')->get();

        $depenses = Depense::selectRaw("TO_CHAR(date_depense, 'YYYY-MM') as periode, SUM(montant) as total")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('periode')->orderBy('periode')->get();

        $categories = Depense::selectRaw("categorie, SUM(montant) as total")
            ->whereBetween('date_depense', [$start, $end])
            ->groupBy('categorie')->orderByDesc('total')->get();

        $depensesMap = $depenses->pluck('total', 'periode')->toArray();
        $benefices = $revenus->map(fn($rev) => [
            "periode" => $rev->periode,
            "benefice" => $rev->total - ($depensesMap[$rev->periode] ?? 0),
        ])->values();

        $topMotos = Moto::query()
            ->select('motos.id', 'motos.immatriculation')
            ->selectRaw("
                COALESCE(SUM(versements.montant_verse), 0)
                - COALESCE(SUM(depenses.montant), 0)
                - COALESCE(SUM(reparations.montant), 0) as benefice
            ")
            ->leftJoin('versements', function ($j) use ($start, $end) {
                $j->on('motos.id', '=', 'versements.moto_id')
                    ->whereBetween('versements.date_versement', [$start, $end]);
            })
            ->leftJoin('depenses', function ($j) use ($start, $end) {
                $j->on('motos.id', '=', 'depenses.moto_id')
                    ->whereBetween('depenses.date_depense', [$start, $end]);
            })
            ->leftJoin('reparations', function ($j) use ($start, $end) {
                $j->on('motos.id', '=', 'reparations.moto_id')
                    ->whereBetween('reparations.date_reparation', [$start, $end]);
            })
            ->groupBy('motos.id', 'motos.immatriculation')
            ->orderByDesc('benefice')
            ->limit(10)
            ->get();

        $topConducteurs = Conducteur::query()
            ->select('conducteurs.id', 'conducteurs.nom', 'conducteurs.prenom')
            ->selectRaw('COALESCE(SUM(versements.montant_verse), 0) as score')
            ->leftJoin('versements', function ($j) use ($start, $end) {
                $j->on('conducteurs.id', '=', 'versements.conducteur_id')
                    ->whereBetween('versements.date_versement', [$start, $end]);
            })
            ->groupBy('conducteurs.id', 'conducteurs.nom', 'conducteurs.prenom')
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        return [
            "revenus_mensuels" => $revenus,
            "depenses_mensuelles" => $depenses,
            "evolution_benefices" => $benefices,
            "repartition_depenses" => $categories,
            "top_motos_rentables" => $topMotos,
            "top_conducteurs" => $topConducteurs,
        ];
    }

    // ------------------------------------------------------------
    // Détails par moto (totaux "depuis toujours")
    // ------------------------------------------------------------

    private function motosDetails()
    {
        return Moto::with([
                'versements:id,moto_id,date_versement,montant_verse',
                'depenses:id,moto_id,montant',
                'reparations:id,moto_id,montant',
                'affectationActive.conducteur:id,nom,prenom',
            ])
            ->get()
            ->map(function ($moto) {
                $revenusGlobal = (float) $moto->versements->sum('montant_verse');
                $depensesGlobal = (float) $moto->depenses->sum('montant') + (float) $moto->reparations->sum('montant');
                $beneficeGlobal = $revenusGlobal - $depensesGlobal;

                // FIX : on exclut les versements sans date avant de formater,
                // sinon ->format() sur null provoque une Fatal Error.
                $graphique = $moto->versements
                    ->filter(fn($v) => $v->date_versement !== null)
                    ->groupBy(fn($v) => $v->date_versement->format('Y-m'))
                    ->map(fn($group) => (float) $group->sum('montant_verse'))
                    ->sortKeys();

                return [
                    'id' => $moto->id,
                    'immatriculation' => $moto->immatriculation,
                    'modele' => $moto->modele,
                    'statut' => $moto->statut,
                    'conducteur_actuel' => $moto->affectationActive?->conducteur
                        ? trim($moto->affectationActive->conducteur->nom . ' ' . $moto->affectationActive->conducteur->prenom)
                        : null,
                    'revenus_total' => $revenusGlobal,
                    'depenses_total' => $depensesGlobal,
                    'benefice_total' => $beneficeGlobal,
                    'graphique_evolution' => $graphique,
                ];
            });
    }

    // ------------------------------------------------------------
    // Détails par conducteur (totaux "depuis toujours")
    // ------------------------------------------------------------

    private function conducteursDetails()
    {
        return Conducteur::with([
                'absences:id,conducteur_id,nombre_jours,retenue',
                'versements:id,conducteur_id,montant_verse',
                'moto:id,immatriculation',
                'affectations' => fn($q) => $q->where('active', true)->with('moto:id,immatriculation'),
            ])
            ->get()
            ->map(function ($conducteur) {
                $totalAbsences = (int) $conducteur->absences->sum('nombre_jours');
                $totalRetenues = (float) $conducteur->absences->sum('retenue');
                $totalVersements = (float) $conducteur->versements->sum('montant_verse');

                $motoActuelle = $conducteur->affectations->first()?->moto?->immatriculation
                    ?? $conducteur->moto?->immatriculation;

                return [
                    'id' => $conducteur->id,
                    'nom' => $conducteur->nom,
                    'prenom' => $conducteur->prenom,
                    'statut' => $conducteur->statut,
                    'moto_actuelle' => $motoActuelle,
                    'total_absences_jours' => $totalAbsences,
                    'total_retenues' => $totalRetenues,
                    'total_versements' => $totalVersements,
                ];
            });
    }

    // ------------------------------------------------------------
    // Évolution du travail de chaque conducteur
    // ------------------------------------------------------------

    public function conducteursEvolution($start, $end)
    {
        $key = "dashboard:conducteurs_evolution:" . md5($start . $end);

        return Cache::remember($key, self::TTL, function () use ($start, $end) {
            return Conducteur::with([
                    'versements' => fn($q) => $q->whereBetween('date_versement', [$start, $end])->orderBy('date_versement'),
                    'absences' => fn($q) => $q->whereBetween('date_debut', [$start, $end]),
                    'moto:id,immatriculation',
                    'affectations' => fn($q) => $q->where('active', true)->with('moto:id,immatriculation'),
                ])
                ->get()
                ->map(function ($conducteur) {
                    // FIX : on exclut les versements sans date_versement
                    // avant tout groupBy/format.
                    $evolutionMensuelle = $conducteur->versements
                        ->filter(fn($v) => $v->date_versement !== null)
                        ->groupBy(fn($v) => $v->date_versement->format('Y-m'))
                        ->map(fn($group) => [
                            'periode' => $group->first()->date_versement->format('Y-m'),
                            'total_verse' => (float) $group->sum('montant_verse'),
                            'total_attendu' => (float) $group->sum('montant_attendu'),
                            'nombre_versements' => $group->count(),
                        ])
                        ->sortBy('periode')
                        ->values();

                    $affectationActive = $conducteur->affectations->first();
                    $motoActuelle = $affectationActive?->moto?->immatriculation
                        ?? $conducteur->moto?->immatriculation;

                    $depuisLe = $affectationActive?->date_debut
                        ? $affectationActive->date_debut->format('Y-m-d')
                        : optional($conducteur->date_embauche)->format('Y-m-d');

                    return [
                        'id' => $conducteur->id,
                        'nom' => $conducteur->nom,
                        'prenom' => $conducteur->prenom,
                        'statut' => $conducteur->statut,
                        'moto_actuelle' => $motoActuelle,
                        'depuis_le' => $depuisLe,
                        'total_verse_periode' => (float) $conducteur->versements->sum('montant_verse'),
                        'total_absences_jours' => (int) $conducteur->absences->sum('nombre_jours'),
                        'total_retenues' => (float) $conducteur->absences->sum('retenue'),
                        'evolution_mensuelle' => $evolutionMensuelle,
                    ];
                })
                ->values();
        });
    }

    // ------------------------------------------------------------
    // Performance de chaque moto
    // ------------------------------------------------------------

    public function motosPerformance($start, $end)
    {
        $key = "dashboard:motos_performance:" . md5($start . $end);

        return Cache::remember($key, self::TTL, function () use ($start, $end) {
            return Moto::with([
                    'versements' => fn($q) => $q->whereBetween('date_versement', [$start, $end]),
                    'depenses' => fn($q) => $q->whereBetween('date_depense', [$start, $end]),
                    'reparations' => fn($q) => $q->whereBetween('date_reparation', [$start, $end]),
                    'affectationActive.conducteur:id,nom,prenom',
                ])
                ->get()
                ->map(function ($moto) {
                    // FIX PRINCIPAL : on écarte toute ligne dont la date est
                    // NULL avant de faire quoi que ce soit avec ->format().
                    // C'était la cause du "Server Error" : des dépenses ou
                    // réparations avec date_depense / date_reparation NULL
                    // faisaient planter ->format('Y-m') (Fatal Error).
                    $versements = $moto->versements->filter(fn($v) => $v->date_versement !== null)->values();
                    $depenses = $moto->depenses->filter(fn($d) => $d->date_depense !== null)->values();
                    $reparations = $moto->reparations->filter(fn($r) => $r->date_reparation !== null)->values();

                    $revenus = (float) $versements->sum('montant_verse');
                    $depensesTotal = (float) $depenses->sum('montant');
                    $reparationsTotal = (float) $reparations->sum('montant');
                    $benefice = $revenus - $depensesTotal - $reparationsTotal;

                    $conducteurActuel = $moto->affectationActive?->conducteur
                        ? trim($moto->affectationActive->conducteur->nom . ' ' . $moto->affectationActive->conducteur->prenom)
                        : null;

                    $periodes = $versements->map(fn($v) => $v->date_versement->format('Y-m'))
                        ->merge($depenses->map(fn($d) => $d->date_depense->format('Y-m')))
                        ->merge($reparations->map(fn($r) => $r->date_reparation->format('Y-m')))
                        ->unique()->sort()->values();

                    $evolution = $periodes->map(function ($periode) use ($versements, $depenses, $reparations) {
                        $r = $versements->filter(fn($v) => $v->date_versement->format('Y-m') === $periode)->sum('montant_verse');
                        $d = $depenses->filter(fn($x) => $x->date_depense->format('Y-m') === $periode)->sum('montant');
                        $rep = $reparations->filter(fn($x) => $x->date_reparation->format('Y-m') === $periode)->sum('montant');

                        return [
                            'periode' => $periode,
                            'revenus' => (float) $r,
                            'depenses' => (float) $d,
                            'reparations' => (float) $rep,
                            'benefice' => (float) ($r - $d - $rep),
                        ];
                    })->values();

                    return [
                        'id' => $moto->id,
                        'immatriculation' => $moto->immatriculation,
                        'modele' => $moto->modele,
                        'statut' => $moto->statut,
                        'conducteur_actuel' => $conducteurActuel,
                        'revenus' => $revenus,
                        'depenses' => $depensesTotal,
                        'reparations' => $reparationsTotal,
                        'benefice' => $benefice,
                        'evolution' => $evolution,
                    ];
                })
                ->sortByDesc('benefice')
                ->values();
        });
    }

    // ------------------------------------------------------------
    // Résumé des versements par moto + total global
    // ------------------------------------------------------------

    public function versementsResume($start, $end)
    {
        $key = "dashboard:versements_resume:" . md5($start . $end);

        return Cache::remember($key, self::TTL, function () use ($start, $end) {
            $parMoto = Moto::query()
                ->select('motos.id', 'motos.immatriculation', 'motos.modele')
                ->selectRaw("
                    COALESCE(SUM(versements.montant_attendu), 0) as total_attendu,
                    COALESCE(SUM(versements.montant_verse), 0) as total_verse,
                    COALESCE(SUM(versements.reste_a_payer), 0) as total_reste,
                    COUNT(versements.id) FILTER (WHERE versements.en_retard) as nb_retards
                ")
                ->leftJoin('versements', function ($join) use ($start, $end) {
                    $join->on('motos.id', '=', 'versements.moto_id')
                        ->whereBetween('versements.date_versement', [$start, $end]);
                })
                ->groupBy('motos.id', 'motos.immatriculation', 'motos.modele')
                ->orderByDesc('total_verse')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'immatriculation' => $m->immatriculation,
                    'modele' => $m->modele,
                    'total_attendu' => (float) $m->total_attendu,
                    'total_verse' => (float) $m->total_verse,
                    'total_reste' => (float) $m->total_reste,
                    'nb_retards' => (int) $m->nb_retards,
                ]);

            $totaux = [
                'total_attendu' => (float) $parMoto->sum('total_attendu'),
                'total_verse' => (float) $parMoto->sum('total_verse'),
                'total_reste' => (float) $parMoto->sum('total_reste'),
                'nb_retards' => (int) $parMoto->sum('nb_retards'),
            ];

            return [
                'par_moto' => $parMoto,
                'totaux' => $totaux,
            ];
        });
    }

    // ------------------------------------------------------------
    // Alertes
    // ------------------------------------------------------------

    private function alertes()
    {
        $alertes = [];

        $motosPanne = Moto::whereIn('statut', ['en_reparation', 'accidentee', 'hors_service'])
            ->get(['immatriculation', 'statut']);
        foreach ($motosPanne as $moto) {
            $alertes[] = [
                'type' => 'panne',
                'moto' => $moto->immatriculation,
                'message' => "Statut : {$moto->statut}",
            ];
        }

        $retards = Conducteur::query()
            ->select('conducteurs.nom', 'conducteurs.prenom')
            ->selectRaw('SUM(versements.reste_a_payer) as dette')
            ->join('versements', 'conducteurs.id', '=', 'versements.conducteur_id')
            ->where('versements.en_retard', true)
            ->groupBy('conducteurs.id', 'conducteurs.nom', 'conducteurs.prenom')
            ->get();

        foreach ($retards as $retard) {
            $alertes[] = [
                'type' => 'retard_paiement',
                'moto' => null,
                'message' => "{$retard->nom} {$retard->prenom} : dette de "
                    . number_format((float) $retard->dette, 0, ',', ' ') . " Ar",
            ];
        }

        return $alertes;
    }

    // ------------------------------------------------------------
    // Véhicules actifs
    // ------------------------------------------------------------

    private function vehiculesActifs()
    {
        return Affectation::where('active', true)
            ->with(['moto:id,immatriculation,type_vehicule', 'conducteur:id,nom,prenom'])
            ->get()
            // FIX : on écarte les affectations orphelines (moto ou
            // conducteur supprimé / introuvable) au lieu de planter sur
            // $a->moto->... / $a->conducteur->... quand la relation est null.
            ->filter(fn($a) => $a->moto !== null && $a->conducteur !== null)
            ->map(fn($a) => [
                'type' => $a->moto->type_vehicule ?? 'moto',
                'nom' => $a->moto->immatriculation,
                'conducteur' => trim($a->conducteur->nom . ' ' . $a->conducteur->prenom),
            ])
            ->values();
    }

    // ------------------------------------------------------------
    // Vue d'ensemble de tous les modules de l'application
    // ------------------------------------------------------------

    public function modules()
    {
        $key = 'dashboard:modules';

        return Cache::remember($key, self::TTL, function () {
            return [
                ['module' => 'motos', 'label' => 'Motos', 'total' => Moto::count(), 'icone' => 'two_wheeler'],
                ['module' => 'conducteurs', 'label' => 'Conducteurs', 'total' => Conducteur::count(), 'icone' => 'people_alt'],
                ['module' => 'versements', 'label' => 'Versements', 'total' => Versement::count(), 'icone' => 'payments'],
                ['module' => 'depenses', 'label' => 'Dépenses', 'total' => Depense::count(), 'icone' => 'trending_down'],
                ['module' => 'reparations', 'label' => 'Réparations', 'total' => Reparation::count(), 'icone' => 'build'],
                ['module' => 'avances', 'label' => 'Avances', 'total' => Avance::count(), 'icone' => 'account_balance_wallet'],
                ['module' => 'absences', 'label' => 'Absences', 'total' => Absence::count(), 'icone' => 'event_busy'],
                ['module' => 'affectations', 'label' => 'Affectations actives', 'total' => Affectation::where('active', true)->count(), 'icone' => 'assignment_ind'],
            ];
        });
    }
}