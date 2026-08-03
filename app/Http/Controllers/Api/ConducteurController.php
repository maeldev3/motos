<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Conducteur;
use App\Models\Versement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConducteurController extends Controller
{
        public function index(Request $request)
    {
        $query = Conducteur::query()
            ->select([
                'id',
                'nom',
                'prenom',
                'sexe',
                'date_naissance',
                'adresse',
                'telephone',
                'cin',
                'moto_id',
                'statut',
                'created_at'
            ])
            ->with([
                'moto:id,immatriculation,marque,modele'
            ]);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('recherche')) {
            $search = $request->recherche;

            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ILIKE', "%{$search}%")
                ->orWhere('prenom', 'ILIKE', "%{$search}%")
                ->orWhere('telephone', 'ILIKE', "%{$search}%")
                ->orWhere('cin', 'ILIKE', "%{$search}%");
            });
        }

        return $this->ok(
            $query->latest('created_at')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'sexe' => 'nullable|in:homme,femme',
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string',
            'telephone' => 'required|string|unique:conducteurs,telephone',
            'cin' => 'nullable|string|unique:conducteurs,cin',
            'numero_permis' => 'nullable|string',
            'date_embauche' => 'nullable|date',
            'photo' => 'nullable|image|max:4096',
            'contact_urgence_nom' => 'nullable|string',
            'contact_urgence_telephone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('conducteurs', 'public');
        }

        $conducteur = Conducteur::create($data);

        return $this->created($conducteur);
    }

    public function show(Conducteur $conducteur)
    {
        $conducteur->load(['moto', 'affectations.moto', 'avances', 'absences']);

        return $this->ok($conducteur);
    }

    public function update(Request $request, Conducteur $conducteur)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string',
            'prenom' => 'sometimes|required|string',
            'sexe' => 'nullable|in:homme,femme',
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string',
            'telephone' => 'sometimes|required|string|unique:conducteurs,telephone,'.$conducteur->id,
            'cin' => 'nullable|string|unique:conducteurs,cin,'.$conducteur->id,
            'numero_permis' => 'nullable|string',
            'date_embauche' => 'nullable|date',
            'photo' => 'nullable|image|max:4096',
            'contact_urgence_nom' => 'nullable|string',
            'contact_urgence_telephone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('conducteurs', 'public');
        }

        $conducteur->update($data);

        return $this->ok($conducteur, 'Conducteur mis à jour');
    }

    public function destroy(Conducteur $conducteur)
    {
        $conducteur->delete();

        return $this->ok(null, 'Conducteur supprimé');
    }

    public function suspendre(Conducteur $conducteur)
    {
        $conducteur->update(['statut' => 'suspendu']);

        return $this->ok($conducteur, 'Conducteur suspendu');
    }

    public function reactiver(Conducteur $conducteur)
    {
        $conducteur->update(['statut' => 'actif']);

        return $this->ok($conducteur, 'Conducteur réactivé');
    }

    /**
     * Affecter / changer la moto d'un conducteur, en conservant l'historique complet.
     */
    public function affecterMoto(Request $request, Conducteur $conducteur)
    {
        $validator = Validator::make($request->all(), [
            'moto_id' => 'required|exists:motos,id',
            'motif_changement' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        // Clôturer l'affectation active précédente
        $ancienne = Affectation::where('conducteur_id', $conducteur->id)->where('active', true)->first();
        if ($ancienne) {
            $ancienne->update([
                'date_fin' => now()->toDateString(),
                'active' => false,
                'motif_changement' => $request->motif_changement ?? $ancienne->motif_changement,
            ]);
        }

        $affectation = Affectation::create([
            'moto_id' => $request->moto_id,
            'conducteur_id' => $conducteur->id,
            'date_debut' => now()->toDateString(),
            'active' => true,
            'motif_changement' => $request->motif_changement,
        ]);

        $conducteur->update(['moto_id' => $request->moto_id]);

        return $this->ok($affectation->load('moto'), 'Moto affectée avec succès');
    }

    public function historiqueMotos(Conducteur $conducteur)
    {
        return $this->ok($conducteur->affectations()->with('moto')->latest('date_debut')->get());
    }

    public function versementInfo(Conducteur $conducteur)
    {
        $conducteur->load('moto');

        if (!$conducteur->moto) {
            return $this->error(
                'Aucune moto affectée.',
                422
            );
        }

        $moto = $conducteur->moto;

        $dernier = Versement::where(
            'conducteur_id',
            $conducteur->id
        )
        ->latest('date_versement')
        ->first();

        return $this->ok([

            'conducteur'=>$conducteur,

            'moto'=>$moto,

            'periodicite'=>'journalier',

            'montant_attendu'=>$moto->montant_versement_mensuel,

            'dernier_versement'=>$dernier

        ]);
    }
}
