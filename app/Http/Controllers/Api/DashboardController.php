<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service
    ) {}

    /**
     * KPIs & Résumé global
     */
    public function index(Request $request)
    {
        $dates = $this->dateRange($request);
        return response()->json([
            'success' => true,
            'data' => $this->service->kpis($dates['start'], $dates['end'])
        ]);
    }

    /**
     * Graphiques, Top motos, Répartition dépenses, Retards paiement
     */
    public function graphiques(Request $request)
    {
        $dates = $this->dateRange($request);
        return response()->json([
            'success' => true,
            'data' => $this->service->graphiques($dates['start'], $dates['end'])
        ]);
    }

    /**
     * Liste détaillée des véhicules actifs avec leur conducteur (Pour la section "Véhicules actifs par conducteur")
     */
    public function vehiculesActifs()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->vehiculesActifs()
        ]);
    }

    private function dateRange(Request $request): array
    {
        return match ($request->get('period', 'month')) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'week'  => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'year'  => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            'custom' => [
                'start' => Carbon::parse($request->start_date)->startOfDay(),
                'end'   => Carbon::parse($request->end_date)->endOfDay()
            ],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]
        };
    }
}