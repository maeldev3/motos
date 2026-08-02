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
        Cache::flush();
    }
}