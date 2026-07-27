<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Versement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VersementController extends Controller
{
    public function index(Request $request)
    {
        $query = Versement::with(['moto', 'conducteur']);

        if ($request->filled('moto_id')) {
            $query->where('moto_id', $request->moto_id);
        }
        if ($request->filled('conducteur_id')) {
            $query->where('conducteur_id', $request->conducteur_id);
        }
        if ($request->filled('periodicite')) {
            $query->where('periodicite', $request->periodicite);
        }
        if ($request->filled('en_retard')) {
            $query->where('en_retard', (bool) $request->en_retard);
        }
        if ($request->filled('mois')) {
            $query->whereMonth('date_versement', $request->mois);
        }
        if ($request->filled('annee')) {
            $query->whereYear('date_versement', $request->annee);
        }

        return $this->ok($query->latest('date_versement')->paginate($request->get('per_page', 20)));
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

    /**
     * Résumé : montant attendu, versé, reste à payer, dette par moto.
     */
    public function resume(Request $request)
    {
        $motoId = $request->get('moto_id');
        $query = Versement::query();
        if ($motoId) {
            $query->where('moto_id', $motoId);
        }

        return $this->ok([
            'montant_attendu_total' => (float) $query->sum('montant_attendu'),
            'montant_verse_total' => (float) $query->sum('montant_verse'),
            'reste_a_payer_total' => (float) $query->sum('reste_a_payer'),
            'nombre_en_retard' => (clone $query)->where('en_retard', true)->count(),
        ]);
    }
}
