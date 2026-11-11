<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePlan extends Model
{
    protected $fillable = [
        'moto_id', 'component', 'interval_km', 'interval_days',
        'last_done_km', 'last_done_at', 'next_due_km', 'next_due_at',
        'active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_done_at' => 'date',
            'next_due_at' => 'date',
            'active' => 'boolean',
        ];
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }
}
