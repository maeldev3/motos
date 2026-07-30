<?php

namespace App\Services;

use App\Models\Versement;
use Illuminate\Support\Facades\Cache;


class VersementService
{


    /**
     * Liste des versements avec filtres
     */
    public function liste(array $filters)
    {

        return Versement::query()

            /*
            |--------------------------------------------------------------------------
            | Eager Loading optimisé
            |--------------------------------------------------------------------------
            */
            ->with([
                'moto:id,immatriculation,modele',
                'conducteur:id,nom,telephone'
            ])


            /*
            |--------------------------------------------------------------------------
            | Filtres dynamiques
            |--------------------------------------------------------------------------
            */

            ->when(
                !empty($filters['moto_id']),
                function ($query) use ($filters) {

                    $query->where(
                        'moto_id',
                        $filters['moto_id']
                    );

                }
            )


            ->when(
                !empty($filters['conducteur_id']),
                function ($query) use ($filters) {

                    $query->where(
                        'conducteur_id',
                        $filters['conducteur_id']
                    );

                }
            )


            ->when(
                !empty($filters['periodicite']),
                function ($query) use ($filters) {

                    $query->where(
                        'periodicite',
                        $filters['periodicite']
                    );

                }
            )


            ->when(
                isset($filters['en_retard']),
                function ($query) use ($filters) {

                    $query->where(
                        'en_retard',
                        $filters['en_retard']
                    );

                }
            )


            ->when(
                !empty($filters['mois']),
                function ($query) use ($filters) {

                    $query->whereMonth(
                        'date_versement',
                        $filters['mois']
                    );

                }
            )


            ->when(
                !empty($filters['annee']),
                function ($query) use ($filters) {

                    $query->whereYear(
                        'date_versement',
                        $filters['annee']
                    );

                }
            )


            ->orderByDesc('date_versement')


            /*
            |--------------------------------------------------------------------------
            | Pagination sécurisée
            |--------------------------------------------------------------------------
            */

            ->paginate(
                min(
                    $filters['per_page'] ?? 20,
                    100
                )
            );

    }




    /**
     * Dashboard résumé versement
     */
    public function resume(?int $motoId = null)
    {


        $cacheKey = "versement_resume_" . ($motoId ?? 'all');



        return Cache::remember(
            $cacheKey,
            now()->addHour(),

            function () use ($motoId) {


                $query = Versement::query();



                if($motoId)
                {

                    $query->where(
                        'moto_id',
                        $motoId
                    );

                }



                return [

                    'montant_attendu_total' => 
                        $query->sum(
                            'montant_attendu'
                        ),



                    'montant_verse_total' => 
                        $query->sum(
                            'montant_verse'
                        ),



                    'reste_a_payer_total' =>
                        $query->sum(
                            'reste_a_payer'
                        ),



                    'nombre_en_retard' =>
                        $query
                        ->where(
                            'en_retard',
                            true
                        )
                        ->count()

                ];

            }
        );

    }



}