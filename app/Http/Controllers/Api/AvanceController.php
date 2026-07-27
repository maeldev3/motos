<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avance;
use App\Models\AvanceRemboursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Avance::with(['conducteur', 'remboursements']);

        if ($request->filled('conducteur_id')) {
            $query->where('conducteur_id', $request->conducteur_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $this->ok($query->latest('date_octroi')->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conducteur_id' => 'required|exists:conducteurs,id',
            'type' => 'required|in:avance,provision',
            'montant' => 'required|numeric|min:0',
            'date_octroi' => 'required|date',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $avance = Avance::create($validator->validated());

        return $this->created($avance->load('conducteur'));
    }

    public function show(Avance $avance)
    {
        return $this->ok($avance->load(['conducteur', 'remboursements']));
    }

    /**
     * Enregistrer un remboursement, déduit automatiquement du solde de l'avance.
     */
    public function rembourser(Request $request, Avance $avance)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:0.01|max:'.$avance->solde,
            'date_remboursement' => 'required|date',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $remboursement = AvanceRemboursement::create([
            'avance_id' => $avance->id,
            ...$validator->validated(),
        ]);

        $avance->montant_rembourse += $request->montant;
        $avance->save(); // le solde est recalculé automatiquement (voir Avance::booted)

        return $this->created($remboursement, 'Remboursement enregistré');
    }

    public function destroy(Avance $avance)
    {
        $avance->delete();

        return $this->ok(null, 'Avance supprimée');
    }
}
