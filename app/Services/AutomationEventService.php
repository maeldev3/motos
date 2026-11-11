<?php

namespace App\Services;

use App\Jobs\SendAutomationEvent;
use Illuminate\Support\Str;

class AutomationEventService
{
    public function dispatch(string $event, array $payload, ?string $key = null): void
    {
        SendAutomationEvent::dispatch(
            $event,
            $payload,
            $key ?: (string) Str::uuid()
        )->onQueue('automation');
    }
}
