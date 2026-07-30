<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Versement;
use App\Services\VersementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VersementController extends Controller
{
    private VersementService $service;



    public function __construct(
        VersementService $service
    )
    {

        $this->service = $service;

    }





    /**
     * Liste paginée
     */
    public function index(Request $request)
    {


        return $this->ok(

            $this->service->liste(
                $request->all()
            )

        );


    }





    /**
     * Résumé financier
     */
    public function resume(Request $request)
    {


        return $this->ok(

            $this->service->resume(
                $request->integer('moto_id')
            )

        );


    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'moto_id' => 'required|exists:motos,id',
            'conducteur_id' => 'nullable|exists:conducteurs,id',
            'date_versement' => 'required|date',
            'periodicite' => 'required|in:journalier,hebdomadaire,mensuel,annuel',
            'montant_attendu' => 'required|numeric|min:0',
            'montant_verse' => 'required|numeric|min:0',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $versement = Versement::create($validator->validated());

        return $this->created($versement->load(['moto', 'conducteur']));
    }

    public function show(Versement $versement)
    {
        return $this->ok($versement->load(['moto', 'conducteur']));
    }

    public function update(Request $request, Versement $versement)
    {
        $validator = Validator::make($request->all(), [
            'montant_verse' => 'sometimes|required|numeric|min:0',
            'montant_attendu' => 'sometimes|required|numeric|min:0',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $versement->update($validator->validated());

        return $this->ok($versement, 'Versement mis à jour');
    }

    public function destroy(Versement $versement)
    {
        $versement->delete();

        return $this->ok(null, 'Versement supprimé');
    }


}
