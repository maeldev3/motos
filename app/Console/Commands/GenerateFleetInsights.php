<?php

namespace App\Console\Commands;

use App\Models\AiInsight;
use App\Models\Moto;
use App\Models\Versement;
use Illuminate\Console\Command;

class GenerateFleetInsights extends Command
{
    protected $signature = 'fleet:generate-insights';
    protected $description = 'Génère des indicateurs prédictifs et anomalies simples';

    public function handle(): int
    {
        $lateCount = Versement::where('en_retard', true)->count();
        $lateAmount = (float) Versement::where('en_retard', true)->sum('reste_a_payer');

        if ($lateCount > 0) {
            AiInsight::create([
                'type' => 'prediction',
                'severity' => $lateCount >= 5 ? 'high' : 'medium',
                'title' => 'Risque de retards de versement',
                'description' => "{$lateCount} versement(s) sont actuellement en retard. Montant restant : ".number_format($lateAmount, 0, ',', ' ')." Ar.",
                'data' => ['late_count' => $lateCount, 'late_amount' => $lateAmount],
                'generated_at' => now(),
                'expires_at' => now()->addDay(),
            ]);
        }

        $motosWithoutOdometer = Moto::where('actif', true)->where('kilometrage_actuel', 0)->count();
        if ($motosWithoutOdometer > 0) {
            AiInsight::create([
                'type' => 'anomaly',
                'severity' => 'info',
                'title' => 'Kilométrage manquant',
                'description' => "{$motosWithoutOdometer} moto(s) active(s) n'ont pas encore de relevé kilométrique.",
                'data' => ['count' => $motosWithoutOdometer],
                'generated_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);
        }

        $this->info('Indicateurs générés.');
        return self::SUCCESS;
    }
}
