<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiInsight;
use App\Models\Alerte;
use App\Models\AutomationRun;
use App\Models\NotificationDelivery;
use Illuminate\Http\Request;

class EnterpriseDashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->ok([
            'automations' => [
                'runs_24h' => AutomationRun::where('created_at', '>=', now()->subDay())->count(),
                'failed_24h' => AutomationRun::where('status', 'failed')->where('created_at', '>=', now()->subDay())->count(),
                'last_runs' => AutomationRun::latest()->limit(10)->get(),
            ],
            'notifications' => [
                'pending' => NotificationDelivery::where('status', 'pending')->count(),
                'failed' => NotificationDelivery::where('status', 'failed')->count(),
                'sent_24h' => NotificationDelivery::where('status', 'sent')->where('sent_at', '>=', now()->subDay())->count(),
            ],
            'alerts' => [
                'open' => Alerte::where('status', 'open')->count(),
                'critical' => Alerte::where('status', 'open')->where('severity', 'critical')->count(),
            ],
            'insights' => AiInsight::where('status', 'new')->latest('generated_at')->limit(10)->get(),
        ]);
    }
}
