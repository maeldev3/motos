<?php

namespace App\Services;


use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Versement;
use App\Models\Depense;
use App\Models\Reparation;
use App\Models\Avance;
use Illuminate\Support\Facades\Cache;



class DashboardService
{


    /**
     * =====================================================
     * KPI PRINCIPAUX
     * =====================================================
     */
    public function kpis($start, $end)
    {


        $key = "dashboard:kpi:" . md5(
            $start . $end
        );



        return Cache::remember(
            $key,
            300,

            function () use ($start, $end) {


                $motos = Moto::selectRaw("

                    COUNT(*) total,

                    COUNT(*) FILTER(
                        WHERE statut='disponible'
                    ) disponibles,


                    COUNT(*) FILTER(
                        WHERE statut='en_reparation'
                    ) reparation,


                    COUNT(*) FILTER(
                        WHERE statut='hors_service'
                    ) hors_service,


                    COUNT(*) FILTER(
                        WHERE statut='en_circulation'
                    ) circulation

                ")
                ->first();




                $conducteurs = Conducteur::selectRaw("

                    COUNT(*) total,

                    COUNT(*) FILTER(
                        WHERE statut='actif'
                    ) actifs,


                    COUNT(*) FILTER(
                        WHERE statut='suspendu'
                    ) suspendus

                ")
                ->first();




                $revenus = Versement::whereBetween(
                    'date_versement',
                    [$start,$end]
                )
                ->sum('montant_verse');




                $depenses = Depense::whereBetween(
                    'date_depense',
                    [$start,$end]
                )
                ->sum('montant');




                $reparations = Reparation::whereBetween(
                    'date_reparation',
                    [$start,$end]
                )
                ->sum('montant');



                $totalDepenses =
                    $depenses + $reparations;



                return [

                    "periode"=>[

                        "de"=>$start->format('Y-m-d'),

                        "a"=>$end->format('Y-m-d')

                    ],



                    "motos"=>[

                        "total"=>(int)$motos->total,

                        "disponibles"=>(int)$motos->disponibles,

                        "reparation"=>(int)$motos->reparation,

                        "hors_service"=>(int)$motos->hors_service,

                        "circulation"=>(int)$motos->circulation

                    ],




                    "conducteurs"=>[

                        "total"=>(int)$conducteurs->total,

                        "actifs"=>(int)$conducteurs->actifs,

                        "suspendus"=>(int)$conducteurs->suspendus

                    ],




                    "finance"=>[


                        "revenus"=>(float)$revenus,


                        "depenses"=>(float)$totalDepenses,


                        "benefice"=>(float)
                        (
                            $revenus -
                            $totalDepenses
                        ),



                        "avances"=>(float)
                        Avance::where(
                            'type',
                            'avance'
                        )
                        ->sum('solde'),



                        "provisions"=>(float)
                        Avance::where(
                            'type',
                            'provision'
                        )
                        ->sum('solde')

                    ]

                ];


            }

        );


    }







    /**
     * =====================================================
     * GRAPHIQUES
     * =====================================================
     */
    public function graphiques($start,$end)
    {


        $key="dashboard:graphs:"
        .md5($start.$end);



        return Cache::remember(
            $key,
            600,


            function() use($start,$end){



                $revenus = Versement::selectRaw("

                    TO_CHAR(
                    date_versement,
                    'YYYY-MM'
                    ) periode,


                    SUM(
                    montant_verse
                    ) total

                ")

                ->whereBetween(
                    'date_versement',
                    [$start,$end]
                )

                ->groupBy('periode')

                ->orderBy('periode')

                ->get();





                $depenses = Depense::selectRaw("

                    TO_CHAR(
                    date_depense,
                    'YYYY-MM'
                    ) periode,


                    SUM(montant) total

                ")

                ->whereBetween(
                    'date_depense',
                    [$start,$end]
                )

                ->groupBy('periode')

                ->orderBy('periode')

                ->get();





                $categories = Depense::selectRaw("

                    categorie,

                    SUM(montant) total

                ")

                ->whereBetween(
                    'date_depense',
                    [$start,$end]
                )

                ->groupBy('categorie')

                ->orderByDesc('total')

                ->get();





                $benefices=[];


                foreach($revenus as $rev)
                {


                    $dep =
                    $depenses->firstWhere(
                        'periode',
                        $rev->periode
                    );


                    $benefices[]=[

                        "periode"=>$rev->periode,

                        "benefice"=>
                        $rev->total -
                        ($dep->total ?? 0)

                    ];


                }






                return [

                    "revenus_mensuels"=>$revenus,

                    "depenses_mensuelles"=>$depenses,

                    "evolution_benefices"=>$benefices,

                    "repartition_depenses"=>$categories,

                    "top_motos_rentables"=>
                    $this->rentabiliteMotos()

                ];

            }

        );


    }







    /**
     * =====================================================
     * REVENUS PAR MOTO
     * =====================================================
     */
    public function revenusMotos($start,$end)
    {


        return Versement::query()

        ->join(
            'motos',
            'motos.id',
            '=',
            'versements.moto_id'
        )


        ->selectRaw("

            motos.immatriculation,

            SUM(
            versements.montant_verse
            ) total

        ")


        ->whereBetween(
            'date_versement',
            [$start,$end]
        )


        ->groupBy(
            'motos.id',
            'motos.immatriculation'
        )


        ->orderByDesc('total')

        ->get();


    }







    /**
     * =====================================================
     * DEPENSES PAR MOTO
     * =====================================================
     */
    public function depensesMotos($start,$end)
    {


        return Depense::query()

        ->join(
            'motos',
            'motos.id',
            '=',
            'depenses.moto_id'
        )


        ->selectRaw("

            motos.immatriculation,

            SUM(depenses.montant)
            total

        ")


        ->whereBetween(
            'date_depense',
            [$start,$end]
        )


        ->groupBy(
            'motos.id',
            'motos.immatriculation'
        )


        ->orderByDesc('total')

        ->get();


    }







    /**
     * =====================================================
     * BENEFICES PAR MOTO
     * =====================================================
     */
    public function beneficesMotos($start,$end)
    {


        $revenus=$this->revenusMotos(
            $start,
            $end
        );


        $depenses=$this->depensesMotos(
            $start,
            $end
        );



        return $revenus->map(function($item)
        use($depenses){


            $dep =
            $depenses->firstWhere(
                'immatriculation',
                $item->immatriculation
            );



            return [

                "moto"=>
                $item->immatriculation,


                "revenus"=>
                $item->total,


                "depenses"=>
                $dep->total ?? 0,


                "benefice"=>
                $item->total -
                ($dep->total ?? 0)

            ];


        });


    }








    /**
     * =====================================================
     * RENTABILITE DEPUIS CREATION
     * =====================================================
     */
    public function rentabiliteMotos()
    {


        return Cache::remember(
            'dashboard:rentabilite',
            3600,


            function(){


                return Moto::query()


                ->select(
                    'motos.immatriculation'
                )


                ->selectRaw("


                COALESCE(
                SUM(versements.montant_verse),
                0)


                -

                COALESCE(
                SUM(depenses.montant),
                0)

                AS benefice


                ")



                ->leftJoin(
                    'versements',
                    'motos.id',
                    '=',
                    'versements.moto_id'
                )


                ->leftJoin(
                    'depenses',
                    'motos.id',
                    '=',
                    'depenses.moto_id'
                )


                ->groupBy(
                    'motos.id',
                    'motos.immatriculation'
                )


                ->orderByDesc(
                    'benefice'
                )


                ->limit(10)

                ->get();



            }

        );


    }








    /**
     * =====================================================
     * CONDUCTEURS EN RETARD
     * =====================================================
     */
    public function retardsPaiement()
    {


        return Cache::remember(
            'dashboard:retards',
            300,


            function(){


                return Conducteur::query()


                ->join(
                    'versements',
                    'conducteurs.id',
                    '=',
                    'versements.conducteur_id'
                )


                ->where(
                    'versements.en_retard',
                    true
                )


                ->selectRaw("


                    conducteurs.id,

                    conducteurs.nom,

                    conducteurs.prenom,


                    COUNT(
                    versements.id
                    )
                    nombre_retard,


                    SUM(
                    versements.reste_a_payer
                    )
                    dette


                ")


                ->groupBy(

                    'conducteurs.id',

                    'conducteurs.nom',

                    'conducteurs.prenom'

                )


                ->orderByDesc(
                    'dette'
                )


                ->get();


            }

        );


    }


}