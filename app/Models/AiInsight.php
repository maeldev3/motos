<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'type', 'severity', 'title', 'description', 'data',
        'status', 'generated_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
