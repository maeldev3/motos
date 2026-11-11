<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Versement;
use App\Observers\VersementObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request): Limit {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Scramble documentation is available only to authenticated administrators.
        Gate::define('viewApiDocs', fn (User $user): bool => $user->isAdministrateur());
    }
}
