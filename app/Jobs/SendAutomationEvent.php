<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAutomationEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 120, 300];

    public function __construct(
        public string $event,
        public array $payload,
        public string $idempotencyKey,
    ) {}

    public function handle(): void
    {
        $url = config('enterprise.n8n_webhook_url');
        $secret = config('enterprise.automation_secret');

        if (!$url || !$secret) {
            Log::info('Automation event retained locally; n8n webhook is not configured.', [
                'event' => $this->event,
                'idempotency_key' => $this->idempotencyKey,
            ]);
            return;
        }

        Http::timeout(15)
            ->retry(2, 500)
            ->withHeaders([
                'X-Automation-Secret' => $secret,
                'Idempotency-Key' => $this->idempotencyKey,
            ])
            ->post($url, [
                'event' => $this->event,
                'idempotency_key' => $this->idempotencyKey,
                'occurred_at' => now()->toIso8601String(),
                'data' => $this->payload,
            ])->throw();
    }
}
