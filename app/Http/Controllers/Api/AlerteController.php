<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerte;
use Illuminate\Http\Request;

class AlerteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 30), 1), 100);
        $query = Alerte::query()->with('alertable')->latest();

        if ($request->boolean('non_lues')) {
            $query->where('lue', false);
        }

        return $this->ok([
            'non_lues' => Alerte::where('lue', false)->count(),
            'items' => $query->paginate($perPage),
        ]);
    }

    public function lire(Alerte $alerte)
    {
        $alerte->update(['lue' => true]);
        return $this->ok($alerte, 'Notification marquée comme lue');
    }

    public function toutLire()
    {
        Alerte::where('lue', false)->update(['lue' => true]);
        return $this->ok(null, 'Toutes les notifications ont été marquées comme lues');
    }

    public function supprimer(Alerte $alerte)
    {
        $alerte->delete();
        return $this->ok(null, 'Notification supprimée');
    }
}
