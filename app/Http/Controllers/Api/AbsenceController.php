<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AbsenceController extends Controller
{
    public function index(Request $request)
    {
        $query = Absence::with('conducteur');

        if ($request->filled('conducteur_id')) {
            $query->where('conducteur_id', $request->conducteur_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $this->ok($query->latest('date_debut')->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conducteur_id' => 'required|exists:conducteurs,id',
            'type' => 'required|in:absence,maladie,conge,accident,autorisation',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'nullable|string',
            'retenue' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $absence = Absence::create($validator->validated());

        return $this->created($absence->load('conducteur'), 'Absence enregistrée, retenue calculée automatiquement');
    }

    public function show(Absence $absence)
    {
        return $this->ok($absence->load('conducteur'));
    }

    public function update(Request $request, Absence $absence)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|in:absence,maladie,conge,accident,autorisation',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after_or_equal:date_debut',
            'motif' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $absence->update($validator->validated());

        return $this->ok($absence, 'Absence mise à jour');
    }

    public function destroy(Absence $absence)
    {
        $absence->delete();

        return $this->ok(null, 'Absence supprimée');
    }
}
