<?php

use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\EnterpriseDashboardController;
use App\Http\Controllers\Api\AutomationWebhookController;
use App\Http\Controllers\Api\AffectationController;
use App\Http\Controllers\Api\AlerteController;
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

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| Base URL: /api
| Authentication: Laravel Sanctum (Authorization: Bearer <token>)
|--------------------------------------------------------------------------
*/

// Public authentication endpoints.
Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth');
});

// All business endpoints require a valid Sanctum token.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Account provisioning is restricted to administrators.
    Route::post('auth/register-utilisateur', [AuthController::class, 'register'])
        ->middleware('role:administrateur')
        ->name('auth.register');

    Route::apiResource('motos', MotoController::class);
    Route::post('motos/{moto}/desactiver', [MotoController::class, 'desactiver']);
    Route::post('motos/{moto}/reactiver', [MotoController::class, 'reactiver']);
    Route::get('motos/{moto}/finances', [MotoController::class, 'finances']);

    Route::apiResource('conducteurs', ConducteurController::class);
    Route::post('conducteurs/{conducteur}/suspendre', [ConducteurController::class, 'suspendre']);
    Route::post('conducteurs/{conducteur}/reactiver', [ConducteurController::class, 'reactiver']);
    Route::post('conducteurs/{conducteur}/affecter-moto', [ConducteurController::class, 'affecterMoto']);
    Route::get('conducteurs/{conducteur}/historique-motos', [ConducteurController::class, 'historiqueMotos']);
    Route::get('conducteurs/{conducteur}/versement', [ConducteurController::class, 'versementInfo']);

    Route::get('affectations', [AffectationController::class, 'index']);
    Route::get('affectations/{affectation}', [AffectationController::class, 'show']);

    Route::get('versements-resume', [VersementController::class, 'resume']);
    Route::apiResource('versements', VersementController::class);

    Route::apiResource('absences', AbsenceController::class);
    Route::apiResource('avances', AvanceController::class);
    Route::post('avances/{avance}/rembourser', [AvanceController::class, 'rembourser']);

    Route::apiResource('reparations', ReparationController::class);
    Route::apiResource('depenses', DepenseController::class);
    Route::get('depenses-par-categorie', [DepenseController::class, 'parCategorie']);

    // Notifications / alerts.
    Route::prefix('alertes')->name('alertes.')->group(function (): void {
        Route::get('/', [AlerteController::class, 'index'])->name('index');
        Route::post('/tout-lire', [AlerteController::class, 'toutLire'])->name('read-all');
        Route::patch('/{alerte}/lire', [AlerteController::class, 'lire'])->name('read');
        Route::delete('/{alerte}', [AlerteController::class, 'supprimer'])->name('delete');
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function (): void {
        Route::get('/', [DashboardController::class, 'full'])->name('full');
        Route::get('/conducteurs-evolution', [DashboardController::class, 'conducteursEvolution']);
        Route::get('/motos-performance', [DashboardController::class, 'motosPerformance']);
        Route::get('/versements-resume', [DashboardController::class, 'versementsResume']);
        Route::get('/modules', [DashboardController::class, 'modules']);
        Route::get('/vehicules-actifs', [DashboardController::class, 'vehiculesActifs']);
    });

    Route::prefix('rapports')->name('rapports.')->group(function (): void {
        Route::get('/global', [RapportController::class, 'global'])->name('global');
        Route::get('/moto/{moto}', [RapportController::class, 'parMoto'])->name('moto');
        Route::get('/export-pdf', [RapportController::class, 'exportPdf'])->name('export-pdf');
    });
});


// Enterprise intelligence endpoints. Existing Flutter endpoints remain unchanged.
Route::middleware('auth:sanctum')->prefix('enterprise')->group(function (): void {
    Route::get('/dashboard', [EnterpriseDashboardController::class, 'index'])
        ->middleware('role:administrateur');
    Route::post('/ai/assistant', [AiAssistantController::class, 'ask'])
        ->middleware('role:administrateur');
});

// Machine-to-machine callback from n8n. Protect with X-Automation-Secret.
Route::post('/automation/events', [AutomationWebhookController::class, 'ingest'])
    ->middleware('automation.secret');
