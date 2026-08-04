<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avance;
use App\Models\AvanceRemboursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class AvanceController extends Controller
{
    public function index(Request $request)
    {

        $perPage = min(
            $request->integer('per_page', 20),
            100
        );
        $query = Avance::query()
            ->select([
                'id',
                'conducteur_id',
                'type',
                'montant',
                'montant_rembourse',
                'solde',
                'date_octroi'
            ])
            ->with([
                'conducteur:id,nom,prenom,telephone',
                'remboursements:id,avance_id,montant,date_remboursement'
            ]);

        if ($request->filled('conducteur_id')) {
            $query->where(
                'conducteur_id',
                $request->conducteur_id
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->type
            );
        }

        return $this->ok(
            $query
                ->orderByDesc('date_octroi')
                ->paginate($perPage)
        );
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

        $data = Cache::remember(
            "avance:" . $avance->id,
            600,
            function () use ($avance) {
                return Avance::query()
                    ->with([
                        'conducteur:id,nom,prenom,telephone',
                        'remboursements:id,avance_id,montant,date_remboursement'
                    ])
                    ->find($avance->id);
            }
        );
        return $this->ok($data);
    }

    /**
     * Enregistrer un remboursement, déduit automatiquement du solde de l'avance.
     */
    public function rembourser(Request $request, Avance $avance)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:0.01|max:' . $avance->solde,
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

    public function update(Request $request, Avance $avance)
    {
        $validator = Validator::make($request->all(), [
            'conducteur_id' => 'required|exists:conducteurs,id',
            'type' => 'required|in:avance,provision',
            // Le montant ne peut pas descendre sous ce qui a déjà été remboursé,
            // sinon le solde (montant - montant_rembourse) deviendrait négatif.
            'montant' => 'required|numeric|min:' . $avance->montant_rembourse,
            'date_octroi' => 'required|date',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $avance->update($validator->validated());

        // show() met en cache l'avance pendant 10 min : il faut invalider cette
        // entrée sinon l'ancienne version resterait servie après une modification.
        Cache::forget("avance:{$avance->id}");

        return $this->ok($avance->fresh('conducteur'), 'Avance mise à jour');
    }

    public function destroy(Avance $avance)
    {
        $avance->delete();

        Cache::forget("avance:{$avance->id}");

        return $this->ok(null, 'Avance supprimée');
    }
}
