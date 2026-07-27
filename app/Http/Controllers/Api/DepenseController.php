<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Depense::with('moto');

        if ($request->filled('moto_id')) {
            $query->where('moto_id', $request->moto_id);
        }
        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }
        if ($request->filled('mois')) {
            $query->whereMonth('date_depense', $request->mois);
        }
        if ($request->filled('annee')) {
            $query->whereYear('date_depense', $request->annee);
        }

        return $this->ok($query->latest('date_depense')->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'moto_id' => 'nullable|exists:motos,id',
            'date_depense' => 'required|date',
            'categorie' => 'required|in:reparation,entretien,assurance,carburant,huile_moteur,pneus,batterie,lavage,parking,carte_grise,taxes,amendes,accessoires,divers',
            'montant' => 'required|numeric|min:0',
            'justificatif' => 'nullable|image|max:4096',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('justificatif')) {
            $data['justificatif'] = $request->file('justificatif')->store('justificatifs', 'public');
        }

        $depense = Depense::create($data);

        return $this->created($depense->load('moto'));
    }

    public function show(Depense $depense)
    {
        return $this->ok($depense->load('moto'));
    }

    public function update(Request $request, Depense $depense)
    {
        $data = $request->validate([
            'categorie' => 'sometimes|required|string',
            'montant' => 'sometimes|required|numeric|min:0',
            'commentaire' => 'nullable|string',
        ]);

        $depense->update($data);

        return $this->ok($depense, 'Dépense mise à jour');
    }

    public function destroy(Depense $depense)
    {
        $depense->delete();

        return $this->ok(null, 'Dépense supprimée');
    }

    public function parCategorie(Request $request)
    {
        $query = Depense::query();
        if ($request->filled('annee')) {
            $query->whereYear('date_depense', $request->annee);
        }

        return $this->ok(
            $query->selectRaw('categorie, SUM(montant) as total')
                ->groupBy('categorie')
                ->orderByDesc('total')
                ->get()
        );
    }
}
