<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Moto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class MotoController extends Controller
{
    // =============================================
    // VERSIONNAGE DU CACHE
    // Le driver "file" (et d'autres) ne supportent pas Cache::tags(),
    // donc on invalide toutes les listes en cache en changeant la version
    // plutôt qu'en essayant de retrouver chaque clé individuellement
    // (impossible, car la clé dépend d'un hash des paramètres de requête).
    // =============================================
    private function cacheVersion(): int
    {
        return Cache::get('motos_cache_version', 0);
    }

    private function bumpCacheVersion(): void
    {
        Cache::forever('motos_cache_version', now()->timestamp);
    }

    public function index(Request $request)
    {
        // 1. PAGINATION & CACHE
        $perPage = $request->get('per_page', 20);
        $cacheKey = 'motos_list_v' . $this->cacheVersion() . '_' . md5(json_encode($request->all()));

        // Le cache stocke les résultats paginés pendant 5 minutes
        return Cache::remember($cacheKey, 300, function () use ($request, $perPage) {
            
            $query = Moto::select([
                'id',
                'immatriculation',
                'marque',
                'modele',
                'statut'
            ]);

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
            $motos = $query->with(['affectationActive:id,moto_id,conducteur_id',
                                  'affectationActive.conducteur:id,nom,prenom,telephone'])
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
        $this->bumpCacheVersion();

        return $this->created($moto);
    }

    public function show(int $id)
    {
        $cacheKey = "moto_show_{$id}";
    
        $moto = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($id) {
    
            return Moto::query()
    
                ->select([
                    'id',
                    'immatriculation',
                    'marque',
                    'modele',
                    'couleur',
                    'annee_fabrication',
                    'numero_chassis',
                    'numero_moteur',
                    'date_achat',
                    'prix_achat',
                    'type_vehicule',
                    'montant_versement_mensuel',
                    'statut',
                    'actif',
                ])
    
                // ->with([
    
                //     'affectationActive' => function ($q) {
                //         $q->select([
                //             'id',
                //             'moto_id',
                //             'conducteur_id',
                //             'active'
                //         ]);
                //     },
    
                //     'affectationActive.conducteur' => function ($q) {
                //         $q->select([
                //             'id',
                //             'nom',
                //             'prenom'
                //         ]);
                //     },
    
                //   'reparations' => function ($q) {
                //         $q->select([
                //             'id',
                //             'moto_id',
                //             'date_reparation',
                //             'type_reparation',
                //             'montant'
                //         ])
                //         ->latest('date_reparation')
                //         ->limit(5);
                //     },

                //     'depenses' => function ($q) {
                //         $q->select([
                //             'id',
                //             'moto_id',
                //             'date_depense',
                //             'categorie',
                //             'montant'
                //         ])
                //         ->latest('date_depense')
                //         ->limit(5);
                //     },
                // ])
    
                ->findOrFail($id);
        });
    
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
        // Pas besoin de purger explicitement 'moto_show_{id}_{updated_at}' :
        // updated_at change avec l'update, donc l'ancienne clé de cache
        // devient automatiquement orpheline (elle expirera seule).
        $this->bumpCacheVersion();

        return $this->ok($moto, 'Moto mise à jour');
    }

    public function destroy(Moto $moto)
    {
        $moto->delete();
        $this->bumpCacheVersion();

        return $this->ok(null, 'Moto supprimée');
    }

    public function desactiver(Moto $moto)
    {
        $moto->update(['actif' => false, 'statut' => 'hors_service']);
        $this->bumpCacheVersion();

        return $this->ok($moto, 'Moto désactivée');
    }

    public function reactiver(Moto $moto)
    {
        $moto->update(['actif' => true, 'statut' => 'disponible']);
        $this->bumpCacheVersion();

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