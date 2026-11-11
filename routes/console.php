<?php

use Illuminate\Support\Facades\Schedule;

// Vérification quotidienne des échéances de paiement (hebdomadaires et mensuelles).
Schedule::command('notifications:versements')->dailyAt('08:00');


// Enterprise automation: short, idempotent scans; heavy work is queued.
Schedule::command('fleet:scan-maintenance')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('fleet:generate-insights')->dailyAt('07:15')->withoutOverlapping();
