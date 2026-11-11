<?php

namespace App\Console\Commands;

use App\Models\Alerte;
use App\Models\MaintenancePlan;
use App\Services\AutomationEventService;
use Illuminate\Console\Command;

class ScanMaintenance extends Command
{
    protected $signature = 'fleet:scan-maintenance';
    protected $description = 'Détecte les maintenances par date et kilométrage';

    public function handle(AutomationEventService $events): int
    {
        $created = 0;

        MaintenancePlan::query()
            ->where('active', true)
            ->with('moto')
            ->chunkById(100, function ($plans) use (&$created, $events) {
                foreach ($plans as $plan) {
                    $moto = $plan->moto;
                    if (!$moto) continue;

                    $dueKm = $plan->next_due_km !== null
                        && $moto->kilometrage_actuel >= $plan->next_due_km;
                    $dueDate = $plan->next_due_at !== null
                        && $plan->next_due_at->startOfDay()->lte(today()->addDays(14));

                    if (!$dueKm && !$dueDate) continue;

                    $key = "maintenance:{$plan->id}:".($plan->next_due_km ?? 'na').':'.($plan->next_due_at?->format('Ymd') ?? 'na');
                    $alert = Alerte::firstOrCreate(
                        ['dedupe_key' => $key],
                        [
                            'type' => 'entretien_moto',
                            'alertable_type' => get_class($moto),
                            'alertable_id' => $moto->id,
                            'message' => "Entretien à prévoir ({$plan->component}) pour la moto {$moto->immatriculation}.",
                            'severity' => ($dueKm || ($plan->next_due_at && $plan->next_due_at->lt(today()))) ? 'high' : 'medium',
                            'status' => 'open',
                            'metadata' => [
                                'maintenance_plan_id' => $plan->id,
                                'current_km' => $moto->kilometrage_actuel,
                                'due_km' => $plan->next_due_km,
                                'due_date' => $plan->next_due_at?->toDateString(),
                            ],
                            'lue' => false,
                        ]
                    );

                    if ($alert->wasRecentlyCreated) {
                        $created++;
                        $events->dispatch('maintenance.alert.created', [
                            'alert_id' => $alert->id,
                            'moto_id' => $moto->id,
                            'immatriculation' => $moto->immatriculation,
                            'component' => $plan->component,
                            'severity' => $alert->severity,
                            'message' => $alert->message,
                        ], $key);
                    }
                }
            });

        $this->info("Alertes de maintenance créées : {$created}");
        return self::SUCCESS;
    }
}
