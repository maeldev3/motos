<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $cacheKey = sprintf(
            'affectations:%s:%s:%s:%s',
            $request->input('moto_id'),
            $request->input('conducteur_id'),
            $request->input('page', 1),
            $perPage
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request, $perPage) {

            return $this->ok(
                Affectation::query()

                    ->select([
                        'id',
                        'moto_id',
                        'conducteur_id',
                        'date_debut',
                        'date_fin',
                        'active'
                    ])

                    ->with([

                        'moto:id,immatriculation,marque,modele,statut,type_vehicule',

                        'conducteur:id,nom,prenom,telephone,statut'

                    ])

                    ->when(
                        $request->filled('moto_id'),
                        fn($q) => $q->where('moto_id', $request->moto_id)
                    )

                    ->when(
                        $request->filled('conducteur_id'),
                        fn($q) => $q->where('conducteur_id', $request->conducteur_id)
                    )

                    ->latest('date_debut')

                    ->simplePaginate($perPage)
            );
        });
    }

    public function show(Affectation $affectation)
    {
        $cacheKey = "affectation:{$affectation->id}:{$affectation->updated_at->timestamp}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($affectation) {

            $affectation->load([

                'moto:id,immatriculation,marque,modele,couleur,statut,type_vehicule',

                'conducteur:id,nom,prenom,telephone,cin,numero_permis,statut'

            ]);

            return $this->ok($affectation);
        });
    }
}