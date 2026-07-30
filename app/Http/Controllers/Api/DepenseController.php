<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class DepenseController extends Controller
{

    /**
     * Liste dépenses optimisée
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page',20),100);

        $cacheKey = 'depenses_' . md5(json_encode($request->all()));


        return Cache::remember($cacheKey,300,function() use($request,$perPage){


            $query = Depense::query()
                ->select([
                    'id',
                    'moto_id',
                    'date_depense',
                    'categorie',
                    'montant',
                    'commentaire'
                ])
                ->with([
                    'moto:id,immatriculation,marque,modele'
                ]);


            /*
            |--------------------------------------------------------------------------
            | FILTRES UTILISANT LES INDEX
            |--------------------------------------------------------------------------
            */


            if($request->filled('moto_id')){

                $query->where(
                    'moto_id',
                    $request->moto_id
                );

            }


            if($request->filled('categorie')){

                $query->where(
                    'categorie',
                    $request->categorie
                );

            }



            /*
            | Recherche par période
            | Plus rapide que whereYear()
            */

            if($request->filled('annee')){

                $query->whereBetween(
                    'date_depense',
                    [
                        $request->annee.'-01-01',
                        $request->annee.'-12-31'
                    ]
                );

            }



            if($request->filled('mois')){


                $debut = now()
                    ->setMonth($request->mois)
                    ->startOfMonth()
                    ->toDateString();


                $fin = now()
                    ->setMonth($request->mois)
                    ->endOfMonth()
                    ->toDateString();



                $query->whereBetween(
                    'date_depense',
                    [
                        $debut,
                        $fin
                    ]
                );
            }



            return $query
                ->orderByDesc('date_depense')
                ->paginate($perPage);

        });

    }



    /**
     * Création dépense
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(),[

            'moto_id'=>'nullable|exists:motos,id',

            'date_depense'=>'required|date',

            'categorie'=>'required',

            'montant'=>'required|numeric|min:0',

            'justificatif'=>'nullable|image|max:4096',

            'commentaire'=>'nullable|string',

        ]);



        if($validator->fails()){

            return $this->error(
                'Validation échouée',
                422,
                $validator->errors()
            );

        }



        $data=$validator->validated();



        if($request->hasFile('justificatif')){

            $data['justificatif']
                =
            $request->file('justificatif')
                ->store('justificatifs','public');

        }



        $depense = Depense::create($data);



        // supprimer cache liste
        Cache::flush();



        return $this->created(
            $depense->load(
                'moto:id,immatriculation,marque,modele'
            )
        );

    }





    /**
     * Détail
     */
    public function show(Depense $depense)
    {


        return Cache::remember(
            'depense_'.$depense->id,
            600,
            function() use($depense){


                return $depense->load(
                    'moto:id,immatriculation,marque,modele'
                );


            }
        );

    }





    /**
     * Modification
     */
    public function update(Request $request,Depense $depense)
    {


        $data=$request->validate([

            'categorie'=>'sometimes|string',

            'montant'=>'sometimes|numeric|min:0',

            'commentaire'=>'nullable|string',

        ]);



        $depense->update($data);


        Cache::flush();



        return $this->ok(
            $depense,
            'Dépense mise à jour'
        );

    }





    public function destroy(Depense $depense)
    {

        $depense->delete();


        Cache::flush();


        return $this->ok(
            null,
            'Dépense supprimée'
        );

    }





    /**
     * Statistique par catégorie
     */
    public function parCategorie(Request $request)
    {


        $annee=$request->annee ?? now()->year;


        return Cache::remember(
            "depenses_stat_$annee",
            3600,
            function() use($annee){



                return Depense::selectRaw(
                    'categorie,
                    SUM(montant) as total'
                )

                ->whereBetween(
                    'date_depense',
                    [
                        "$annee-01-01",
                        "$annee-12-31"
                    ]
                )

                ->groupBy('categorie')

                ->orderByDesc('total')

                ->get();


            }
        );


    }

}