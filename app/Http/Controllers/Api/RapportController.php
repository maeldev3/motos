<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Models\Moto;
use App\Models\Reparation;
use App\Models\Versement;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    /**
     * Rapport global (revenus, dépenses, bénéfices) sur une période.
     * Périodes : journalier / hebdomadaire / mensuel / annuel, ou debut+fin personnalisés.
     */
    public function global(Request $request)
    {
        [$debut, $fin] = $this->resoudrePeriode($request);

        $revenus = (float) Versement::whereBetween('date_versement', [$debut, $fin])->sum('montant_verse');
        $depenses = (float) Depense::whereBetween('date_depense', [$debut, $fin])->sum('montant');
        $reparations = (float) Reparation::whereBetween('date_reparation', [$debut, $fin])->sum('montant');

        return $this->ok([
            'periode' => ['debut' => $debut, 'fin' => $fin],
            'revenus' => $revenus,
            'depenses' => $depenses + $reparations,
            'benefice' => $revenus - ($depenses + $reparations),
            'detail' => [
                'versements' => Versement::whereBetween('date_versement', [$debut, $fin])->get(),
                'depenses' => Depense::whereBetween('date_depense', [$debut, $fin])->get(),
                'reparations' => Reparation::whereBetween('date_reparation', [$debut, $fin])->get(),
            ],
        ]);
    }

    public function parMoto(Request $request, Moto $moto)
    {
        [$debut, $fin] = $this->resoudrePeriode($request);

        return $this->ok([
            'moto' => $moto,
            'periode' => ['debut' => $debut, 'fin' => $fin],
            'revenus' => $moto->revenusEntre($debut, $fin),
            'depenses' => $moto->depensesEntre($debut, $fin),
            'benefice' => $moto->beneficeEntre($debut, $fin),
        ]);
    }

    /**
     * Export PDF (nécessite le package barryvdh/laravel-dompdf, déjà présent dans composer.json).
     * Exécuter : composer install, puis publier la vue si besoin.
     */
    public function exportPdf(Request $request)
    {
        [$debut, $fin] = $this->resoudrePeriode($request);

        $revenus = (float) Versement::whereBetween('date_versement', [$debut, $fin])->sum('montant_verse');
        $depenses = (float) Depense::whereBetween('date_depense', [$debut, $fin])->sum('montant');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rapports.global', [
            'debut' => $debut,
            'fin' => $fin,
            'revenus' => $revenus,
            'depenses' => $depenses,
            'benefice' => $revenus - $depenses,
        ]);

        return $pdf->download("rapport_{$debut}_{$fin}.pdf");
    }

    private function resoudrePeriode(Request $request): array
    {
        if ($request->filled('debut') && $request->filled('fin')) {
            return [$request->debut, $request->fin];
        }

        return match ($request->get('periode', 'mensuel')) {
            'journalier' => [now()->toDateString(), now()->toDateString()],
            'hebdomadaire' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'annuel' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }
}
