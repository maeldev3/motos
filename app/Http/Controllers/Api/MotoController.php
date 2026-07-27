<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Moto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MotoController extends Controller
{
    public function index(Request $request)
    {
        $query = Moto::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_vehicule')) {
            $query->where('type_vehicule', $request->type_vehicule);
        }
        if ($request->filled('recherche')) {
            $q = $request->recherche;
            $query->where(function ($qr) use ($q) {
                $qr->where('immatriculation', 'like', "%{$q}%")
                    ->orWhere('marque', 'like', "%{$q}%")
                    ->orWhere('modele', 'like', "%{$q}%");
            });
        }

        $motos = $query->latest()->paginate($request->get('per_page', 20));

        return $this->ok($motos);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'immatriculation' => 'required|string|unique:motos,immatriculation',
            'marque' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
            'couleur' => 'nullable|string',
            'annee_fabrication' => 'nullable|integer|min:1980|max:'.(date('Y') + 1),
            'numero_chassis' => 'nullable|string|unique:motos,numero_chassis',
            'numero_moteur' => 'nullable|string|unique:motos,numero_moteur',
            'date_achat' => 'nullable|date',
            'prix_achat' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:4096',
            'type_vehicule' => 'in:moto,voiture',
            'montant_versement_mensuel' => 'nullable|numeric|min:0',
            'montant_versement_journalier' => 'nullable|numeric|min:0',
            'statut' => 'in:disponible,en_circulation,en_reparation,en_entretien,accidentee,hors_service,vendue',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        // Valeurs par défaut selon le cahier des charges
        if (($data['type_vehicule'] ?? 'moto') === 'voiture') {
            $data['montant_versement_journalier'] = $data['montant_versement_journalier'] ?? 100000;
        } else {
            $data['montant_versement_mensuel'] = $data['montant_versement_mensuel'] ?? 600000;
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('motos', 'public');
        }

        $moto = Moto::create($data);

        return $this->created($moto);
    }

    public function show(Moto $moto)
    {
        $moto->load(['affectationActive.conducteur', 'reparations', 'depenses']);

        return $this->ok($moto);
    }

    public function update(Request $request, Moto $moto)
    {
        $validator = Validator::make($request->all(), [
            'immatriculation' => 'sometimes|required|string|unique:motos,immatriculation,'.$moto->id,
            'marque' => 'sometimes|required|string',
            'modele' => 'sometimes|required|string',
            'couleur' => 'nullable|string',
            'annee_fabrication' => 'nullable|integer',
            'numero_chassis' => 'nullable|string|unique:motos,numero_chassis,'.$moto->id,
            'numero_moteur' => 'nullable|string|unique:motos,numero_moteur,'.$moto->id,
            'date_achat' => 'nullable|date',
            'prix_achat' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:4096',
            'type_vehicule' => 'in:moto,voiture',
            'montant_versement_mensuel' => 'nullable|numeric|min:0',
            'montant_versement_journalier' => 'nullable|numeric|min:0',
            'statut' => 'in:disponible,en_circulation,en_reparation,en_entretien,accidentee,hors_service,vendue',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('motos', 'public');
        }

        $moto->update($data);

        return $this->ok($moto, 'Moto mise à jour');
    }

    public function destroy(Moto $moto)
    {
        $moto->delete();

        return $this->ok(null, 'Moto supprimée');
    }

    public function desactiver(Moto $moto)
    {
        $moto->update(['actif' => false, 'statut' => 'hors_service']);

        return $this->ok($moto, 'Moto désactivée');
    }

    public function reactiver(Moto $moto)
    {
        $moto->update(['actif' => true, 'statut' => 'disponible']);

        return $this->ok($moto, 'Moto réactivée');
    }

    /**
     * Revenus / dépenses / bénéfices d'une moto sur une période donnée.
     */
    public function finances(Request $request, Moto $moto)
    {
        $debut = $request->get('debut', now()->startOfMonth()->toDateString());
        $fin = $request->get('fin', now()->endOfMonth()->toDateString());

        return $this->ok([
            'moto' => $moto,
            'periode' => ['debut' => $debut, 'fin' => $fin],
            'revenus' => $moto->revenusEntre($debut, $fin),
            'depenses' => $moto->depensesEntre($debut, $fin),
            'benefice' => $moto->beneficeEntre($debut, $fin),
        ]);
    }
}
