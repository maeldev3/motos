<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use Illuminate\Http\Request;

class AutomationWebhookController extends Controller
{
    public function ingest(Request $request)
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'max:120'],
            'idempotency_key' => ['required', 'string', 'max:191'],
            'occurred_at' => ['nullable', 'date'],
            'data' => ['nullable', 'array'],
        ]);

        $existing = AutomationRun::where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            return $this->ok(['duplicate' => true], 'Événement déjà traité');
        }

        $run = AutomationRun::create([
            'workflow' => $data['event'],
            'idempotency_key' => $data['idempotency_key'],
            'status' => 'received',
            'started_at' => now(),
            'finished_at' => now(),
            'summary' => $data['data'] ?? [],
        ]);

        return $this->created(['accepted' => true, 'run_id' => $run->id], 'Événement reçu');
    }
}
