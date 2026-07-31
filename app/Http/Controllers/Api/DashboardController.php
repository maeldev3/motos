<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service
    ) {}

    public function full(Request $request)
    {
        $dates = $this->dateRange($request);
        return response()->json([
            'success' => true,
            'data' => $this->service->fullDashboard($dates['start'], $dates['end'])
        ]);
    }

    private function dateRange(Request $request): array
    {
        return match ($request->get('period', 'month')) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'week'  => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'year'  => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            'custom' => [
                'start' => Carbon::parse($request->start_date)->startOfDay(),
                'end'   => Carbon::parse($request->end_date)->endOfDay()
            ],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]
        };
    }
}