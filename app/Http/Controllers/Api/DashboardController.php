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
     * Dashboard complet (KPIs + graphiques + détails + évolution
     * conducteurs + performance motos + résumé versements + modules).
     * Mis en cache côté service (10 min) par période.
     */
    public function full(Request $request)
    {
        return response()->json(['message'=> 'ok']);
        // $dates = $this->dateRange($request);

        // return response()->json([
        //     'success' => true,
        //     'data' => $this->service->fullDashboard($dates['start'], $dates['end']),
        // ]);
    }

    /**
     * Évolution du travail (versements) et des absences de chaque
     * conducteur, mois par mois, sur la période demandée.
     * GET /api/dashboard/conducteurs-evolution
     */
    public function conducteursEvolution(Request $request)
    {
        $dates = $this->dateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->service->conducteursEvolution($dates['start'], $dates['end']),
        ]);
    }

    /**
     * Revenus / dépenses / réparations / bénéfice par moto, avec
     * évolution mensuelle.
     * GET /api/dashboard/motos-performance
     */
    public function motosPerformance(Request $request)
    {
        $dates = $this->dateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->service->motosPerformance($dates['start'], $dates['end']),
        ]);
    }

    /**
     * Résumé des versements par moto + totaux globaux.
     * GET /api/dashboard/versements-resume
     */
    public function versementsResume(Request $request)
    {
        $dates = $this->dateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->service->versementsResume($dates['start'], $dates['end']),
        ]);
    }

    /**
     * Vue d'ensemble de tous les modules disponibles dans l'application.
     * GET /api/dashboard/modules
     */
    public function modules()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->modules(),
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
                'end'   => Carbon::parse($request->end_date)->endOfDay(),
            ],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
        };
    }
}

/*
|--------------------------------------------------------------------------
| routes/api.php — à ajouter (ou remplacer le bloc existant)
|--------------------------------------------------------------------------
|
| Route::prefix('dashboard')->group(function () {
|     Route::get('/', [DashboardController::class, 'full']);
|     Route::get('/conducteurs-evolution', [DashboardController::class, 'conducteursEvolution']);
|     Route::get('/motos-performance', [DashboardController::class, 'motosPerformance']);
|     Route::get('/versements-resume', [DashboardController::class, 'versementsResume']);
|     Route::get('/modules', [DashboardController::class, 'modules']);
|     Route::get('/vehicules-actifs', [DashboardController::class, 'full']); // ou une méthode dédiée si besoin d'un payload plus léger
| });
|
*/