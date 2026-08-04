<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reparation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReparationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reparation::query()
            ->select([
                'id',
                'moto_id',
                'date_reparation',
                'type_reparation',
                'montant',
                'garage',
            ]);
            // ->with([
            //     'moto:id,immatriculation,marque,modele'
            // ]);
    
        if ($request->filled('moto_id')) {
            $query->where('moto_id', $request->moto_id);
        }
    
        if ($request->filled('type_reparation')) {
            $query->where('type_reparation', $request->type_reparation);
        }
    
        return $this->ok(
            $query
                ->latest('date_reparation')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'moto_id' => 'required|exists:motos,id',
            'date_reparation' => 'required|date',
            'type_reparation' => 'required|in:vidange,changement_pneus,chaine,batterie,embrayage,moteur,carburateur,freins,suspension,peinture,accident,revision_complete,autres',
            'description' => 'nullable|string',
            'garage' => 'nullable|string',
            'mecanicien' => 'nullable|string',
            'kilometrage' => 'nullable|integer|min:0',
            'pieces_remplacees' => 'nullable|string',
            'montant' => 'required|numeric|min:0',
            'photo_facture' => 'nullable|image|max:4096',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('photo_facture')) {
            $data['photo_facture'] = $request->file('photo_facture')->store('factures/reparations', 'public');
        }

        $reparation = Reparation::create($data);

        // Passer automatiquement la moto en "en_reparation"
        $reparation->moto->update(['statut' => 'en_reparation']);

        return $this->created($reparation->load('moto'));
    }

    public function show(Reparation $reparation)
    {
        return $this->ok($reparation->load('moto'));
    }

    public function update(Request $request, Reparation $reparation)
    {
        $validator = Validator::make($request->all(), [
            'moto_id' => 'sometimes|required|exists:motos,id',
            'date_reparation' => 'sometimes|required|date',
            'type_reparation' => 'sometimes|required|in:vidange,changement_pneus,chaine,batterie,embrayage,moteur,carburateur,freins,suspension,peinture,accident,revision_complete,autres',
            'description' => 'nullable|string',
            'garage' => 'nullable|string',
            'mecanicien' => 'nullable|string',
            'kilometrage' => 'nullable|integer|min:0',
            'pieces_remplacees' => 'nullable|string',
            'montant' => 'sometimes|required|numeric|min:0',
            'photo_facture' => 'nullable|image|max:4096',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('photo_facture')) {
            $data['photo_facture'] = $request->file('photo_facture')->store('factures/reparations', 'public');
        }

        $ancienMotoId = $reparation->moto_id;

        $reparation->update($data);

        // Si la moto a changé, on remet l'ancienne moto disponible et on passe la nouvelle en "en_reparation"
        if (isset($data['moto_id']) && $data['moto_id'] != $ancienMotoId) {
            $reparation->moto()->associate($data['moto_id']); // au cas où la relation est encore en cache
            $reparation->refresh();
            $reparation->moto->update(['statut' => 'en_reparation']);
        }

        return $this->ok($reparation->load('moto'), 'Réparation mise à jour');
    }
    public function destroy(Reparation $reparation)
    {
        $reparation->delete();

        return $this->ok(null, 'Réparation supprimée');
    }
}
