<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Moto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class MotoController extends Controller
{
    public function index(Request $request)
    {
        // 1. PAGINATION & CACHE
        $perPage = $request->get('per_page', 20);
        $cacheKey = 'motos_list_' . md5(json_encode($request->all()));

        // Le cache stocke les résultats paginés pendant 5 minutes
        return Cache::remember($cacheKey, 300, function () use ($request, $perPage) {
            
            $query = Moto::query();

            // 2. INDEX SQL UTILISÉS ICI (statut, type_vehicule)
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

            // 3. EAGER LOADING (Charge les relations en 1 seule requête SQL)
            // On charge 'affectationActive.conducteur' pour éviter le N+1 lors de l'affichage
            $motos = $query->with(['affectationActive.conducteur'])
                           ->latest()
                           ->paginate($perPage);

            return $this->ok($motos);
        });
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
        // 1. CACHE PAR ID
        $cacheKey = 'moto_show_' . $moto->id . '_' . $moto->updated_at->timestamp;
        
        return Cache::remember($cacheKey, 600, function () use ($moto) {
            // 2. EAGER LOADING DES RELATIONS
            $moto->load(['affectationActive.conducteur', 'reparations', 'depenses']);
            return $this->ok($moto);
        });
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

        // 4. QUEUE / JOB (Pour les calculs longs, ex: calculs sur plusieurs années)
        // Si la période dépasse 1 an, on met le calcul en file d'attente.
        $diffInDays = \Carbon\Carbon::parse($debut)->diffInDays($fin);
        
        if ($diffInDays > 365) {
            // Déclenche un Job asynchrone (exemple de logique)
            // CalculateMotoFinances::dispatch($moto->id, $debut, $fin);
            // return $this->ok(['message' => 'Le calcul est en cours, veuillez patienter...']);
        }

        // Sinon, calcul en temps réel
        return $this->ok([
            'moto' => $moto,
            'periode' => ['debut' => $debut, 'fin' => $fin],
            'revenus' => $moto->revenusEntre($debut, $fin),
            'depenses' => $moto->depensesEntre($debut, $fin),
            'benefice' => $moto->beneficeEntre($debut, $fin),
        ]);
    }
}
