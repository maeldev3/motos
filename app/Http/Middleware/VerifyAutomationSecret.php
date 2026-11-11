<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAutomationSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('enterprise.automation_secret');
        $provided = (string) $request->header('X-Automation-Secret');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 401);
        }

        return $next($request);
    }
}
