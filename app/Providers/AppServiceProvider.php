<?php

namespace App\Providers;

use App\Models\Versement;
use App\Observers\VersementObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Versement::observe(VersementObserver::class);
        // Depense::observe(VersementObserver::class); // ou son propre Observer
        // Reparation::observe(VersementObserver::class);
    }
}
