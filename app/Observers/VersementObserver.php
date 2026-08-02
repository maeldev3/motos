<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class VersementObserver
{
    public function saved($versement): void
    {
        $this->flushDashboardCache();
    }

    public function deleted($versement): void
    {
        $this->flushDashboardCache();
    }

    private function flushDashboardCache(): void
    {
        // FIX : les clés dashboard:full:*, dashboard:motos_performance:*
        // et dashboard:conducteurs_evolution:* sont hashées par période
        // (md5(start.end)), donc pas de clé fixe à supprimer -> on utilise
        // les tags Cache (nécessite un driver qui les supporte : redis,
        // memcached). Si tu es sur "file" ou "database", les tags ne
        // marchent pas ; il faudra alors stocker explicitement chaque clé
        // générée dans un registre pour pouvoir les flush.
        Cache::tags(['dashboard'])->flush();
    }
}