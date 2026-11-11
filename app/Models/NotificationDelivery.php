<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'alerte_id', 'channel', 'recipient', 'template', 'payload',
        'status', 'attempts', 'idempotency_key', 'sent_at', 'last_error',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'sent_at' => 'datetime'];
    }

    public function alerte(): BelongsTo
    {
        return $this->belongsTo(Alerte::class, 'alerte_id');
    }
}
