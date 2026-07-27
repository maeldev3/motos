<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Usage dans les routes : ->middleware('role:administrateur,gestionnaire')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        if (! empty($roles) && ! in_array($user->role, $roles)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé pour votre rôle.'], 403);
        }

        return $next($request);
    }
}
