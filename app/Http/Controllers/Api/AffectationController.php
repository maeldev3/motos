<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use Illuminate\Http\Request;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $query = Affectation::with(['moto', 'conducteur']);

        if ($request->filled('moto_id')) {
            $query->where('moto_id', $request->moto_id);
        }
        if ($request->filled('conducteur_id')) {
            $query->where('conducteur_id', $request->conducteur_id);
        }

        return $this->ok($query->latest('date_debut')->paginate($request->get('per_page', 20)));
    }

    public function show(Affectation $affectation)
    {
        return $this->ok($affectation->load(['moto', 'conducteur']));
    }
}
