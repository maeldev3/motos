<?php

use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\AffectationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvanceController;
use App\Http\Controllers\Api\ConducteurController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepenseController;
use App\Http\Controllers\Api\MotoController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\ReparationController;
use App\Http\Controllers\Api\VersementController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// Routes publiques
// ---------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});
Route::get('affectations', [AffectationController::class, 'index']);
// ---------------------------------------------------------------------
// Routes protégées (Sanctum)
// ---------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    // Route::get('health', fn () => response()->json(['status' => 'ok']));
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Motos
    Route::apiResource('motos', MotoController::class);
    Route::post('motos/{moto}/desactiver', [MotoController::class, 'desactiver']);
    Route::post('motos/{moto}/reactiver', [MotoController::class, 'reactiver']);
    Route::get('motos/{moto}/finances', [MotoController::class, 'finances']);

    // Conducteurs
    Route::apiResource('conducteurs', ConducteurController::class);
    Route::post('conducteurs/{conducteur}/suspendre', [ConducteurController::class, 'suspendre']);
    Route::post('conducteurs/{conducteur}/reactiver', [ConducteurController::class, 'reactiver']);
    Route::post('conducteurs/{conducteur}/affecter-moto', [ConducteurController::class, 'affecterMoto']);
    Route::get('conducteurs/{conducteur}/historique-motos', [ConducteurController::class, 'historiqueMotos']);
    Route::get('conducteurs/{conducteur}/versement', [ConducteurController::class, 'versementInfo']);

    // Affectations (historique global)

    Route::get('affectations/{affectation}', [AffectationController::class, 'show']);

    // Versements
    Route::get('versements-resume', [VersementController::class, 'resume']);
    Route::apiResource('versements', VersementController::class);

    // Absences
    Route::apiResource('absences', AbsenceController::class);

    // Avances / provisions
    // NOTE: 'update' est désormais autorisé (modification depuis l'app mobile).
    // Assure-toi que AvanceController possède bien une méthode update(Request $request, Avance $avance).
    Route::apiResource('avances', AvanceController::class);
    Route::post('avances/{avance}/rembourser', [AvanceController::class, 'rembourser']);

    // Réparations
    Route::apiResource('reparations', ReparationController::class);

    // Dépenses
    Route::apiResource('depenses', DepenseController::class);
    Route::get('depenses-par-categorie', [DepenseController::class, 'parCategorie']);


    // =======================================================
    // DASHBOARD
    // =======================================================

    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'full']);
        Route::get('/conducteurs-evolution', [DashboardController::class, 'conducteursEvolution']);
        Route::get('/motos-performance', [DashboardController::class, 'motosPerformance']);
        Route::get('/versements-resume', [DashboardController::class, 'versementsResume']);
        Route::get('/modules', [DashboardController::class, 'modules']);
        Route::get('/vehicules-actifs', [DashboardController::class, 'vehiculesActifs']);
    });

    // Rapports
    Route::get('rapports/global', [RapportController::class, 'global']);
    Route::get('rapports/moto/{moto}', [RapportController::class, 'parMoto']);
    Route::get('rapports/export-pdf', [RapportController::class, 'exportPdf']);

    // Réservé aux administrateurs : gestion des comptes utilisateurs
    Route::middleware('role:administrateur')->group(function () {
        Route::post('auth/register-utilisateur', [AuthController::class, 'register']);
    });
});